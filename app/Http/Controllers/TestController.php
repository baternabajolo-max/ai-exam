<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\Question;
use App\Models\QuestionBankItem;
use App\Models\Test;
use App\Models\User;
use App\Notifications\FacultyExamApprovalDecisionNotification;
use App\Notifications\FacultyExamApprovalRequestedNotification;
use App\Services\QuestionBankQualityAnalyzer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TestController extends Controller
{
    public function __construct(private QuestionBankQualityAnalyzer $qualityAnalyzer) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $request->validate([
            'view_all' => ['sometimes', 'boolean'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ]);
        $showAll = $request->boolean('view_all');
        $groupByCollege = $user->role === 'super-admin';
        $facultyCourses = collect();
        $departments = collect();
        $filterCourses = collect();
        $selectedDepartmentId = null;
        $selectedCourseId = null;
        if ($groupByCollege) {
            $departments = Department::query()
                ->orderBy('name')
                ->get(['id', 'name']);
            if ($request->filled('department_id')) {
                $selectedDepartmentId = $request->integer('department_id');
                abort_unless($departments->contains('id', $selectedDepartmentId), 404);
                $filterCourses = Course::query()
                    ->where('department_id', $selectedDepartmentId)
                    ->orderBy('name')
                    ->get(['id', 'name']);
            }
            if ($request->filled('course_id')) {
                abort_unless($selectedDepartmentId !== null, 404);
                $selectedCourseId = $request->integer('course_id');
                abort_unless($filterCourses->contains('id', $selectedCourseId), 404);
            }
        } elseif ($user->role === 'admin' && $user->department_id !== null) {
            $facultyCourses = Course::query()
                ->where('department_id', $user->department_id)
                ->orderBy('name')
                ->get(['id', 'name']);
            if ($request->filled('course_id')) {
                $selectedCourseId = $request->integer('course_id');
                abort_unless($facultyCourses->contains('id', $selectedCourseId), 404);
            }
        }
        $testsQuery = Test::query()
            ->where('is_scheduled_exam', true)
            ->where(fn ($query) => $query
                ->whereNull('approval_status')
                ->orWhere('approval_status', '!=', Test::APPROVAL_STATUS_REJECTED))
            ->when($groupByCollege && $selectedDepartmentId !== null, fn ($query) => $query
                ->where('department_id', $selectedDepartmentId))
            ->when($selectedCourseId !== null, fn ($query) => $query->where('course_id', $selectedCourseId))
            ->when($user->role === 'admin', fn ($query) => $query
                ->whereIn('course_id', $facultyCourses->pluck('id'))
                ->where(fn ($query) => $query
                    ->where(fn ($query) => $query
                        ->where('approval_status', 'approved')
                        ->where('is_published', true))
                    ->orWhere('created_by', $user->id)))
            ->when($groupByCollege, fn ($query) => $query->orderBy('department_id'))
            ->orderBy('course_id')
            ->latest('available_from')
            ->orderByDesc('id');
        $totalTests = (clone $testsQuery)->count();
        $pageSize = $showAll ? max(10, $totalTests) : 10;
        $tests = $testsQuery
            ->with([
                'course:id,name',
                'creator:id,name',
                'questions:id,test_id,question_bank_item_id,question,options,correct_answer,topic,image_path,difficulty',
                'questions.questionBankItem.author:id,name',
            ])
            ->withCount(['questions', 'results'])
            ->paginate($pageSize)
            ->withQueryString();

        return view('admin.tests.index', compact(
            'tests',
            'showAll',
            'groupByCollege',
            'departments',
            'filterCourses',
            'selectedDepartmentId',
            'facultyCourses',
            'selectedCourseId',
        ));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['admin', 'super-admin'], true), 403);
        $validated = $request->validate([
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ]);
        $departments = $user->role === 'super-admin'
            ? Department::query()->orderBy('name')->get(['id', 'name'])
            : collect();
        $availableCourses = $user->role === 'admin'
            ? ($user->department_id === null
                ? collect()
                : Course::query()
                    ->where('department_id', $user->department_id)
                    ->with('department:id,name')
                    ->orderBy('name')
                    ->get())
            : Course::query()
                ->with('department:id,name')
                ->orderBy('department_id')
                ->orderBy('name')
                ->get();
        $selectedDepartmentId = isset($validated['department_id'])
            ? (int) $validated['department_id']
            : null;
        $selectedCourseId = isset($validated['course_id'])
            ? (int) $validated['course_id']
            : null;

        abort_if(
            $selectedDepartmentId !== null && ! $departments->contains('id', $selectedDepartmentId),
            404,
        );
        if ($selectedCourseId !== null) {
            $selectedCourse = $availableCourses->firstWhere('id', $selectedCourseId);
            abort_if($selectedCourse === null, 404);
            $courseDepartmentId = (int) $selectedCourse->department_id;
            abort_if(
                $selectedDepartmentId !== null && $selectedDepartmentId !== $courseDepartmentId,
                404,
            );
            $selectedDepartmentId ??= $courseDepartmentId;
        }

        $courses = $user->role === 'admin'
            ? $availableCourses
            : ($selectedDepartmentId === null
            ? collect()
            : $availableCourses
                ->where('department_id', $selectedDepartmentId)
                ->values());
        $questionBankItems = $selectedCourseId !== null
            ? QuestionBankItem::query()
                ->where('course_id', $selectedCourseId)
                ->where('is_active', true)
                ->with([
                    'course:id,name,department_id',
                    'author:id,name',
                    'subtopic:id,title',
                    'topicRecord:id,name',
                ])
                ->orderBy('topic')
                ->orderBy('id')
                ->get()
            : collect();
        $questionBankTopics = $questionBankItems
            ->groupBy(static function (QuestionBankItem $item): string {
                $topicName = trim((string) ($item->topicRecord?->name ?? $item->topic ?? 'General'));

                return mb_strtolower($topicName, 'UTF-8');
            })
            ->map(static function (Collection $items, string $topicKey): array {
                $firstItem = $items->first();

                return [
                    'key' => $topicKey,
                    'name' => trim((string) ($firstItem->topicRecord?->name ?? $firstItem->topic ?? 'General')),
                    'count' => $items->count(),
                ];
            })
            ->sortBy(static fn (array $topic): string => mb_strtolower($topic['name'], 'UTF-8'))
            ->values();
        $difficultyCounts = $questionBankItems->countBy(static function (QuestionBankItem $item): string {
            $difficulty = strtolower((string) ($item->difficulty ?? 'moderate'));

            return in_array($difficulty, ['easy', 'hard'], true) ? $difficulty : 'moderate';
        });
        $totalQuestions = $questionBankItems->count();
        $topicsCount = $questionBankTopics->count();
        $easyCount = $difficultyCounts->get('easy', 0);
        $moderateCount = $difficultyCounts->get('moderate', 0);
        $hardCount = $difficultyCounts->get('hard', 0);
        $questionQualityMetrics = $this->qualityAnalyzer->analyze($questionBankItems);

        return view('admin.tests.create', compact(
            'courses',
            'departments',
            'questionBankItems',
            'questionBankTopics',
            'questionQualityMetrics',
            'totalQuestions',
            'topicsCount',
            'easyCount',
            'moderateCount',
            'hardCount',
            'selectedCourseId',
            'selectedDepartmentId',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $creator = $request->user();
        abort_unless(in_array($creator->role, ['admin', 'super-admin'], true), 403);

        $validationRules = [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'available_from' => ['required', 'date'],
            'available_until' => ['nullable', 'date', 'after:available_from'],
            'time_limit' => ['required', 'integer', 'min:1', 'max:300'],
            'question_bank_item_ids' => ['required', 'array', 'min:1', 'max:100'],
            'question_bank_item_ids.*' => ['required', 'integer', 'distinct', 'exists:question_bank_items,id'],
        ];
        $validated = $request->validate($validationRules);

        $course = Course::query()->findOrFail($validated['course_id']);
        abort_if(
            $creator->role === 'admin' && ! $this->facultyCanUseCourse($creator, $course->id),
            404,
        );
        $items = QuestionBankItem::query()
            ->where('course_id', $course->id)
            ->whereIn('id', $validated['question_bank_item_ids'])
            ->with('topicRecord:id,name')
            ->get();
        if ($items->count() !== count($validated['question_bank_item_ids'])) {
            abort(404);
        }
        if ($items->contains(fn (QuestionBankItem $item): bool => ! $item->is_active)) {
            throw ValidationException::withMessages([
                'question_bank_item_ids' => 'One or more selected questions are disabled and cannot be added to an examination.',
            ]);
        }
        $topicLabel = $items
            ->map(static fn (QuestionBankItem $item): string => trim((string) (
                $item->topicRecord?->name ?? $item->topic ?? 'General'
            )))
            ->filter()
            ->unique(static fn (string $topicName): string => mb_strtolower($topicName, 'UTF-8'))
            ->sortBy(static fn (string $topicName): string => mb_strtolower($topicName, 'UTF-8'))
            ->implode(', ');
        $topicLabel = $topicLabel !== '' ? $topicLabel : 'General';
        $items = $items->shuffle();

        $startsAt = Carbon::parse($validated['available_from']);
        $closesAt = isset($validated['available_until'])
            ? Carbon::parse($validated['available_until'])
            : null;

        $isFacultyRequest = $creator->role === 'admin';
        $test = DB::transaction(function () use ($creator, $course, $validated, $items, $topicLabel, $startsAt, $closesAt, $isFacultyRequest): Test {
            $test = Test::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'topic' => $topicLabel,
                'time_limit' => (int) $validated['time_limit'],
                'duration_minutes' => (int) $validated['time_limit'],
                'department_id' => $course->department_id,
                'course_id' => $course->id,
                'created_by' => $creator->id,
                'question_type' => 'multiple_choice',
                'is_general' => false,
                'is_scheduled_exam' => true,
                'is_published' => ! $isFacultyRequest,
                'approval_status' => $isFacultyRequest ? 'pending' : 'approved',
                'reviewed_by' => $isFacultyRequest ? null : $creator->id,
                'reviewed_at' => $isFacultyRequest ? null : now(),
                'available_from' => $startsAt,
                'available_until' => $closesAt,
            ]);

            foreach ($items as $item) {
                Question::create([
                    'test_id' => $test->id,
                    'question_bank_item_id' => $item->id,
                    'topic_id' => $item->topic_id,
                    'question_type' => Test::QUESTION_TYPE_MULTIPLE_CHOICE,
                    'question' => $item->question,
                    'options' => $item->options,
                    'correct_answer' => $item->correct_answer,
                    'explanation' => $item->explanation,
                    'topic' => $item->topic ?: ($item->topicRecord?->name ?? 'General'),
                    'image_path' => $item->image_path,
                    'difficulty' => $item->difficulty ?? 'moderate',
                ]);
            }

            return $test;
        });

        if ($isFacultyRequest) {
            $admins = User::query()->where('role', 'super-admin')->get();
            Notification::send($admins, new FacultyExamApprovalRequestedNotification($test, $creator));
        }

        $indexRoute = $isFacultyRequest ? 'admin.tests.index' : 'super-admin.tests.index';
        $message = $isFacultyRequest
            ? 'Examination request submitted for Admin approval.'
            : 'Scheduled examination created for '.$test->scheduleStatus().'.';

        return redirect()->route($indexRoute)->with('success', $message);
    }

    public function edit(Request $request, Test $test): View
    {
        $this->authorizeExamModification($request, $test);
        abort_if($test->results()->exists(), 404);

        $test->load([
            'course:id,name,department_id',
            'questions:id,test_id,question_bank_item_id',
        ]);
        $currentQuestionBankItemIds = $test->questions->pluck('question_bank_item_id')->filter()->all();
        $questionBankItems = QuestionBankItem::query()
            ->where('course_id', $test->course_id)
            ->where(function ($query) use ($currentQuestionBankItemIds): void {
                $query->where('is_active', true)
                    ->orWhereIn('id', $currentQuestionBankItemIds);
            })
            ->with(['author:id,name'])
            ->orderBy('topic')
            ->orderBy('id')
            ->get();

        return view('admin.tests.edit', compact('test', 'questionBankItems'));
    }

    public function update(Request $request, Test $test): RedirectResponse
    {
        $creator = $request->user();
        $this->authorizeExamModification($request, $test);
        abort_if($test->results()->exists(), 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'topic' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'available_from' => ['required', 'date', 'after:now'],
            'available_until' => ['nullable', 'date', 'after:available_from'],
            'time_limit' => ['required', 'integer', 'min:1', 'max:300'],
            'question_count' => ['required', 'integer', 'min:1', 'max:100'],
            'question_bank_item_ids' => ['required', 'array', 'min:1', 'max:100'],
            'question_bank_item_ids.*' => ['required', 'integer', 'distinct', 'exists:question_bank_items,id'],
        ]);

        if (count($validated['question_bank_item_ids']) !== (int) $validated['question_count']) {
            return back()
                ->withErrors(['question_bank_item_ids' => 'Select exactly the number of questions you entered.'])
                ->withInput();
        }

        $itemsById = QuestionBankItem::query()
            ->where('course_id', $test->course_id)
            ->whereIn('id', $validated['question_bank_item_ids'])
            ->get()
            ->keyBy('id');
        if ($itemsById->count() !== count($validated['question_bank_item_ids'])) {
            abort(404);
        }
        $currentQuestionBankItemIds = $test->questions()
            ->whereNotNull('question_bank_item_id')
            ->pluck('question_bank_item_id')
            ->map(static fn (int|string $itemId): int => (int) $itemId)
            ->all();
        $disabledItems = $itemsById->filter(
            fn (QuestionBankItem $item): bool => ! $item->is_active
                && ! in_array($item->id, $currentQuestionBankItemIds, true),
        );
        if ($disabledItems->isNotEmpty()) {
            throw ValidationException::withMessages([
                'question_bank_item_ids' => 'One or more selected questions are disabled and cannot be added to an examination.',
            ]);
        }

        $items = collect($validated['question_bank_item_ids'])
            ->map(fn (int $itemId): QuestionBankItem => $itemsById->get($itemId));
        $startsAt = Carbon::parse($validated['available_from']);
        $closesAt = isset($validated['available_until'])
            ? Carbon::parse($validated['available_until'])
            : null;
        $requiresAdminReview = $creator->role === 'admin'
            && in_array($test->approval_status, [
                Test::APPROVAL_STATUS_APPROVED,
                Test::APPROVAL_STATUS_REJECTED,
            ], true);

        DB::transaction(function () use ($test, $validated, $items, $startsAt, $closesAt, $requiresAdminReview): void {
            $test->update([
                'title' => $validated['title'],
                'topic' => $validated['topic'],
                'description' => $validated['description'] ?? null,
                'available_from' => $startsAt,
                'available_until' => $closesAt,
                'time_limit' => (int) $validated['time_limit'],
                'duration_minutes' => (int) $validated['time_limit'],
                'approval_status' => $requiresAdminReview ? Test::APPROVAL_STATUS_PENDING : $test->approval_status,
                'is_published' => $requiresAdminReview ? false : $test->is_published,
                'rejection_comment' => $requiresAdminReview ? null : $test->rejection_comment,
                'reviewed_by' => $requiresAdminReview ? null : $test->reviewed_by,
                'reviewed_at' => $requiresAdminReview ? null : $test->reviewed_at,
            ]);
            $test->questions()->delete();

            foreach ($items as $item) {
                Question::create([
                    'test_id' => $test->id,
                    'question_bank_item_id' => $item->id,
                    'topic_id' => $item->topic_id,
                    'question_type' => Test::QUESTION_TYPE_MULTIPLE_CHOICE,
                    'question' => $item->question,
                    'options' => $item->options,
                    'correct_answer' => $item->correct_answer,
                    'explanation' => $item->explanation,
                    'topic' => $item->topic ?? $validated['topic'],
                    'image_path' => $item->image_path,
                    'difficulty' => $item->difficulty ?? 'moderate',
                ]);
            }
        });

        if ($requiresAdminReview) {
            $admins = User::query()->where('role', 'super-admin')->get();
            Notification::send($admins, new FacultyExamApprovalRequestedNotification($test, $creator));
        }

        $indexRoute = $creator->role === 'admin' ? 'admin.tests.index' : 'super-admin.tests.index';
        $message = $requiresAdminReview
            ? 'Examination updated and resubmitted for Admin approval.'
            : 'Examination updated.';

        return redirect()->route($indexRoute)->with('success', $message);
    }

    public function print(Request $request, Test $test): View
    {
        $user = $request->user();
        abort_unless($test->is_scheduled_exam, 404);

        if ($user->role === 'admin') {
            abort_unless(
                $this->facultyCanUseCourse($user, $test->course_id)
                    && ($test->created_by === $user->id
                        || ($test->approval_status === Test::APPROVAL_STATUS_APPROVED && $test->is_published)),
                404,
            );
        }

        $test->load([
            'course:id,name,department_id',
            'course.department:id,name',
            'department:id,name',
            'questions:id,test_id,question,options,image_path',
        ]);

        return view('admin.tests.print', compact('test'));
    }

    public function approve(Request $request, Test $test): RedirectResponse
    {
        abort_unless($request->user()->role === 'super-admin' && $test->is_scheduled_exam, 404);
        abort_unless($test->approval_status === 'pending', 404);

        $test->update([
            'approval_status' => 'approved',
            'is_published' => true,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);
        $this->notifyFacultyOfDecision($test, Test::APPROVAL_STATUS_APPROVED);

        return back()->with('success', 'Examination approved and published.');
    }

    public function reject(Request $request, Test $test): RedirectResponse
    {
        abort_unless($request->user()->role === 'super-admin' && $test->is_scheduled_exam, 404);
        abort_unless($test->approval_status === 'pending', 404);
        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $test->update([
            'approval_status' => 'rejected',
            'is_published' => false,
            'rejection_comment' => $validated['comment'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);
        $this->notifyFacultyOfDecision($test, Test::APPROVAL_STATUS_REJECTED);

        return back()->with('success', 'Examination request rejected.');
    }

    public function markFacultyNotificationRead(Request $request, string $notification): RedirectResponse
    {
        $facultyNotification = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();
        $facultyNotification->markAsRead();

        return back();
    }

    public function destroy(Request $request, Test $test): RedirectResponse
    {
        $this->authorizeExamModification($request, $test);

        if ($test->results()->exists()) {
            return back()->withErrors([
                'exam' => 'This examination has student results and cannot be deleted.',
            ]);
        }

        $test->delete();

        $indexRoute = $request->user()->role === 'admin' ? 'admin.tests.index' : 'super-admin.tests.index';

        return redirect()->route($indexRoute)->with('success', 'Scheduled examination deleted.');
    }

    private function authorizeExamModification(Request $request, Test $test): void
    {
        $user = $request->user();
        abort_unless(
            in_array($user->role, ['admin', 'super-admin'], true)
                && $test->isEditableBy($user)
                && ($user->role !== 'admin' || $this->facultyCanUseCourse($user, $test->course_id)),
            404,
        );
    }

    private function facultyCanUseCourse(User $faculty, int $courseId): bool
    {
        return $faculty->department_id !== null
            && Course::query()
                ->whereKey($courseId)
                ->where('department_id', $faculty->department_id)
                ->exists();
    }

    private function notifyFacultyOfDecision(Test $test, string $decision): void
    {
        $faculty = User::query()
            ->whereKey($test->created_by)
            ->where('role', 'admin')
            ->first();

        if ($faculty !== null) {
            $faculty->notify(new FacultyExamApprovalDecisionNotification($test, $decision));
        }
    }
}
