<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionBankItem;
use App\Models\Test;
use App\Models\User;
use App\Notifications\FacultyExamApprovalRequestedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class TestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $tests = Test::query()
            ->where('is_scheduled_exam', true)
            ->when($user->role === 'admin', fn ($query) => $query
                ->where('department_id', $user->department_id)
                ->where(fn ($query) => $query
                    ->where(fn ($query) => $query
                        ->where('approval_status', 'approved')
                        ->where('is_published', true))
                    ->orWhere('created_by', $user->id)))
            ->with([
                'course:id,name',
                'creator:id,name',
                'questions:id,test_id,question_bank_item_id,question,options,correct_answer,topic',
                'questions.questionBankItem.author:id,name',
            ])
            ->withCount(['questions', 'results'])
            ->latest('available_from')
            ->paginate(20);

        return view('admin.tests.index', compact('tests'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['admin', 'super-admin'], true), 403);
        $validated = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ]);
        $courses = Course::query()
            ->when($user->role === 'admin', fn ($query) => $query->where('department_id', $user->department_id))
            ->with('department:id,name')
            ->orderBy('department_id')
            ->orderBy('name')
            ->get();
        abort_if(
            isset($validated['course_id']) && ! $courses->contains('id', (int) $validated['course_id']),
            404,
        );
        $questionBankItems = isset($validated['course_id'])
            ? QuestionBankItem::query()
                ->where('course_id', $validated['course_id'])
                ->with(['course:id,name,department_id', 'author:id,name', 'subtopic:id,title'])
                ->orderBy('topic')
                ->orderBy('id')
                ->get()
            : collect();
        $selectedCourseId = $validated['course_id'] ?? null;

        return view('admin.tests.create', compact('courses', 'questionBankItems', 'selectedCourseId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $creator = $request->user();
        abort_unless(in_array($creator->role, ['admin', 'super-admin'], true), 403);

        $validationRules = [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'topic' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'available_from' => ['required', 'date'],
            'available_until' => ['nullable', 'date', 'after:available_from'],
            'time_limit' => ['required', 'integer', 'min:1', 'max:300'],
            'question_count' => ['required', 'integer', 'min:1', 'max:100'],
            'question_bank_item_ids' => ['required', 'array', 'min:1', 'max:100'],
            'question_bank_item_ids.*' => ['required', 'integer', 'distinct', 'exists:question_bank_items,id'],
        ];
        $validated = $request->validate($validationRules);
        if (count($validated['question_bank_item_ids']) !== (int) $validated['question_count']) {
            return back()
                ->withErrors(['question_bank_item_ids' => 'Select exactly the number of questions you entered.'])
                ->withInput();
        }

        $course = Course::query()->findOrFail($validated['course_id']);
        abort_if(
            $creator->role === 'admin' && $course->department_id !== $creator->department_id,
            404,
        );
        $items = QuestionBankItem::query()
            ->where('course_id', $course->id)
            ->whereIn('id', $validated['question_bank_item_ids'])
            ->get();
        if ($items->count() !== count($validated['question_bank_item_ids'])) {
            abort(404);
        }
        $items = $items->shuffle();

        $startsAt = Carbon::parse($validated['available_from']);
        $closesAt = isset($validated['available_until'])
            ? Carbon::parse($validated['available_until'])
            : null;

        $isFacultyRequest = $creator->role === 'admin';
        $test = DB::transaction(function () use ($creator, $course, $validated, $items, $startsAt, $closesAt, $isFacultyRequest): Test {
            $test = Test::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'topic' => $validated['topic'],
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
                    'question_type' => Test::QUESTION_TYPE_MULTIPLE_CHOICE,
                    'question' => $item->question,
                    'options' => $item->options,
                    'correct_answer' => $item->correct_answer,
                    'explanation' => $item->explanation,
                    'topic' => $item->topic,
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

        return back()->with('success', 'Examination approved and published.');
    }

    public function reject(Request $request, Test $test): RedirectResponse
    {
        abort_unless($request->user()->role === 'super-admin' && $test->is_scheduled_exam, 404);
        abort_unless($test->approval_status === 'pending', 404);

        $test->update([
            'approval_status' => 'rejected',
            'is_published' => false,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Examination request rejected.');
    }

    public function destroy(Request $request, Test $test): RedirectResponse
    {
        $admin = $request->user();
        abort_unless(
            $admin->role === 'super-admin'
                && $test->is_scheduled_exam,
            404,
        );

        if ($test->results()->exists()) {
            return back()->withErrors([
                'exam' => 'This examination has student results and cannot be deleted.',
            ]);
        }

        $test->delete();

        return redirect()->route('super-admin.tests.index')->with('success', 'Scheduled examination deleted.');
    }
}
