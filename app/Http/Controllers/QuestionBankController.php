<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\Question;
use App\Models\QuestionBankItem;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\QuestionBankItemDisabledNotification;
use App\Services\QuestionBankQualityAnalyzer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class QuestionBankController extends Controller
{
    public function __construct(private QuestionBankQualityAnalyzer $qualityAnalyzer) {}

    public function index(Request $request): View|RedirectResponse
    {
        $data = $this->questionBankData($request);
        if ($data['selectedTopic'] !== null) {
            return redirect()->route('admin.question-bank.topics.show', $data['selectedTopic']);
        }

        return view('admin.question-bank.index', $data + ['showTopicPage' => false]);
    }

    public function showTopic(Request $request, Topic $topic): View
    {
        $this->ensureFacultyCanUseCourse($request->user(), $topic->course_id);
        abort_unless($topic->is_folder, 404);
        $request->query->set('course_id', (string) $topic->course_id);
        $request->query->set('topic_id', (string) $topic->id);

        return view('admin.question-bank.index', $this->questionBankData($request) + ['showTopicPage' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function questionBankData(Request $request, bool $readOnly = false): array
    {
        $user = $request->user();
        abort_unless($readOnly ? $user->role === 'super-admin' : $user->role === 'admin', 404);
        $rules = [
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard'])],
            'health' => ['nullable', Rule::in(['all', 'healthy', 'needs_review', 'flagged', 'untested'])],
        ];
        if ($readOnly) {
            $rules['department_id'] = ['nullable', 'integer', 'exists:departments,id'];
        }
        $validated = $request->validate($rules);

        if ($readOnly) {
            $departments = Department::query()->orderBy('name')->get(['id', 'name']);
            $selectedDepartmentId = isset($validated['department_id'])
                ? (int) $validated['department_id']
                : null;
            abort_if(isset($validated['course_id']) && $selectedDepartmentId === null, 404);
            $filterCourses = Course::query()
                ->orderBy('name')
                ->get(['id', 'name', 'department_id']);
            $courses = $selectedDepartmentId === null
                ? collect()
                : $filterCourses->where('department_id', $selectedDepartmentId)->values();
        } else {
            abort_unless($user->role === 'admin', 404);
            $departments = collect();
            $filterCourses = collect();
            $selectedDepartmentId = null;
            $courses = $user->department_id === null
                ? collect()
                : Course::query()
                    ->where('department_id', $user->department_id)
                    ->orderBy('name')
                    ->get(['id', 'name', 'department_id']);
        }

        $selectedCourseId = isset($validated['course_id'])
            ? (int) $validated['course_id']
            : (! $readOnly && ! $request->query->has('course_id') && $courses->count() === 1
                ? $courses->first()->id
                : null);
        $course = $courses->firstWhere('id', $selectedCourseId);
        $canFlagQuestions = $readOnly || (
            $user->role === 'admin'
            && $user->department_id !== null
            && $course?->department_id === $user->department_id
        );
        abort_if($selectedCourseId !== null && ! $courses->contains('id', $selectedCourseId), 404);
        abort_if($readOnly && $selectedCourseId !== null && $selectedDepartmentId === null, 404);
        $selectedTopicId = isset($validated['topic_id']) ? (int) $validated['topic_id'] : null;
        $selectedDifficulty = $validated['difficulty'] ?? null;
        $selectedHealth = $validated['health'] ?? 'all';
        abort_if($selectedTopicId !== null && $selectedCourseId === null, 404);

        $topics = $selectedCourseId === null
            ? collect()
            : Topic::query()
                ->where('course_id', $selectedCourseId)
                ->where('is_folder', true)
                ->with('createdBy:id,name')
                ->withCount('bankItems as questions_count')
                ->orderBy('name')
                ->get();
        $selectedTopic = $selectedTopicId === null
            ? null
            : $topics->firstWhere('id', $selectedTopicId);
        abort_if($selectedTopicId !== null && $selectedTopic === null, 404);
        $topicHasOtherFacultyQuestions = ! $readOnly && $selectedTopic !== null
            && $selectedTopic->bankItems()
                ->where(function (Builder $query) use ($user): void {
                    $query->whereNull('author_id')
                        ->orWhere('author_id', '!=', $user->id);
                })
                ->exists();

        $itemsQuery = QuestionBankItem::query()
            ->where('course_id', $selectedCourseId)
            ->with([
                'author:id,name',
                'course:id,name',
                'topicRecord:id,course_id,name',
            ]);
        if ($selectedTopic === null) {
            $itemsQuery->whereKey([]);
        } else {
            $itemsQuery->where('topic_id', $selectedTopic->id);
            if ($selectedDifficulty === 'medium') {
                $itemsQuery->where(function (Builder $query): void {
                    $query->whereIn('difficulty', ['medium', 'moderate'])
                        ->orWhereNull('difficulty');
                });
            } elseif ($selectedDifficulty !== null) {
                $itemsQuery->where('difficulty', $selectedDifficulty);
            }
        }
        $perPage = 20;
        if ($selectedHealth === 'all') {
            $items = $itemsQuery->latest()->paginate($perPage)->withQueryString();
            $questionQualityMetrics = $this->qualityAnalyzer->analyze($items->getCollection());
        } else {
            $allItems = $itemsQuery->latest()->get();
            $allQualityMetrics = $this->qualityAnalyzer->analyze($allItems);
            $matchingItems = $allItems
                ->filter(fn (QuestionBankItem $item): bool => ($allQualityMetrics[$item->id]['quality_status'] ?? 'untested') === $selectedHealth)
                ->values();
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $items = new LengthAwarePaginator(
                $matchingItems->forPage($currentPage, $perPage)->values(),
                $matchingItems->count(),
                $perPage,
                $currentPage,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'query' => $request->query(),
                ],
            );
            $pageItemIds = $items->getCollection()->modelKeys();
            $questionQualityMetrics = array_intersect_key($allQualityMetrics, array_fill_keys($pageItemIds, true));
        }

        return [
            'items' => $items,
            'questionQualityMetrics' => $questionQualityMetrics,
            'departments' => $departments,
            'filterCourses' => $filterCourses,
            'courses' => $courses,
            'course' => $course,
            'canFlagQuestions' => $canFlagQuestions,
            'selectedDepartmentId' => $selectedDepartmentId,
            'selectedCourseId' => $selectedCourseId,
            'topics' => $topics,
            'selectedTopic' => $selectedTopic,
            'topicHasOtherFacultyQuestions' => $topicHasOtherFacultyQuestions,
            'selectedDifficulty' => $selectedDifficulty,
            'selectedHealth' => $selectedHealth,
            'readOnly' => $readOnly,
        ];
    }

    public function storeTopic(Request $request): RedirectResponse
    {
        $faculty = $request->user();
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);
        $courseId = (int) $validated['course_id'];
        $this->ensureFacultyCanUseCourse($faculty, $courseId);

        $name = trim($validated['name']);
        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Enter a topic name.',
            ]);
        }

        $nameKey = mb_strtolower($name, 'UTF-8');
        if (
            Topic::query()
                ->where('course_id', $courseId)
                ->where('is_folder', true)
                ->where('name_key', $nameKey)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'name' => 'A topic with this name already exists in this program.',
            ]);
        }

        $topic = Topic::create([
            'course_id' => $courseId,
            'created_by' => $faculty->id,
            'is_folder' => true,
            'name' => $name,
        ]);

        return redirect()->route('admin.question-bank.index', [
            'course_id' => $courseId,
        ])->with('success', 'Topic created. You can now add questions.');
    }

    public function updateTopic(Request $request, Topic $topic): RedirectResponse
    {
        $this->ensureFacultyOwnsTopic($request->user(), $topic);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $name = trim($validated['name']);
        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Enter a topic name.',
            ]);
        }

        $nameKey = mb_strtolower($name, 'UTF-8');
        if (
            Topic::query()
                ->where('course_id', $topic->course_id)
                ->where('is_folder', true)
                ->where('name_key', $nameKey)
                ->where('id', '!=', $topic->id)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'name' => 'A topic with this name already exists in this program.',
            ]);
        }

        DB::transaction(function () use ($topic, $name): void {
            $topic->name = $name;
            $topic->save();
            $topic->bankItems()->update(['topic' => $name]);
        });

        return redirect()->route('admin.question-bank.topics.show', $topic)
            ->with('success', 'Topic folder updated.');
    }

    public function destroyTopic(Request $request, Topic $topic): RedirectResponse
    {
        $faculty = $request->user();
        $this->ensureFacultyOwnsTopic($faculty, $topic);
        $courseId = $topic->course_id;
        $imagePaths = DB::transaction(function () use ($topic, $faculty): array {
            $lockedTopic = Topic::query()->whereKey($topic->id)->lockForUpdate()->firstOrFail();
            $bankItems = $lockedTopic->bankItems()
                ->lockForUpdate()
                ->get(['id', 'image_path']);

            $hasQuestionsByOtherFaculty = $lockedTopic->bankItems()
                ->where(function (Builder $query) use ($faculty): void {
                    $query->whereNull('author_id')
                        ->orWhere('author_id', '!=', $faculty->id);
                })
                ->exists();
            if ($hasQuestionsByOtherFaculty) {
                throw ValidationException::withMessages([
                    'topic' => 'This shared folder contains questions from other faculty and cannot be deleted.',
                ]);
            }

            $lockedTopic->bankItems()->delete();
            $lockedTopic->delete();

            return $bankItems->pluck('image_path')->filter()->unique()->values()->all();
        });

        foreach ($imagePaths as $imagePath) {
            $this->deleteQuestionImageWhenUnreferenced($imagePath, null);
        }

        return redirect()->route('admin.question-bank.index', ['course_id' => $courseId])
            ->with('success', 'Topic folder and its bank questions were deleted. Existing exam copies remain unchanged.');
    }

    public function updateStatus(Request $request, QuestionBankItem $questionBankItem): RedirectResponse
    {
        $user = $request->user();
        if ($user->role === 'admin') {
            $this->ensureFacultyOwnsQuestion($user, $questionBankItem);
        } else {
            abort_unless($user->role === 'super-admin', 404);
        }
        abort_if($questionBankItem->topic_id === null, 404);
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $wasActive = $questionBankItem->is_active;
        $isActive = (bool) $validated['is_active'];
        $questionBankItem->update(['is_active' => $isActive]);

        if ($user->role === 'super-admin' && $wasActive && ! $isActive && $questionBankItem->author !== null) {
            $questionBankItem->author->notify(new QuestionBankItemDisabledNotification($questionBankItem, $user));
        }

        $redirect = $user->role === 'super-admin'
            ? redirect()->route('super-admin.question-bank.index', [
                'department_id' => $questionBankItem->course()->value('department_id'),
                'course_id' => $questionBankItem->course_id,
                'topic_id' => $questionBankItem->topic_id,
            ])
            : redirect()->route('admin.question-bank.topics.show', $questionBankItem->topic_id);

        return $redirect->with('success', $isActive ? 'Question enabled for future exams.' : 'Question disabled for future exams.');
    }

    public function updateRevisionFlag(Request $request, QuestionBankItem $questionBankItem): JsonResponse
    {
        $this->ensureCanFlagQuestion($request->user(), $questionBankItem);
        abort_if($questionBankItem->topic_id === null, 404);
        $validated = $request->validate([
            'needs_revision' => ['required', 'boolean'],
        ]);

        $questionBankItem->update([
            'needs_revision' => (bool) $validated['needs_revision'],
        ]);
        $qualityMetrics = $this->qualityAnalyzer->analyze(collect([$questionBankItem]))[$questionBankItem->id];

        return response()->json([
            'needs_revision' => $questionBankItem->needs_revision,
            'quality_status' => $qualityMetrics['quality_status'],
            'unflagged_quality_status' => $qualityMetrics['unflagged_quality_status'],
        ]);
    }

    public function adminIndex(Request $request): View
    {
        $data = $this->questionBankData($request, true);

        return view('admin.question-bank.index', $data + [
            'showTopicPage' => $data['selectedTopic'] !== null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $faculty = $request->user();
        $data = $this->validatedQuestionData($request, $faculty);
        $questionImage = $data['question_image'];
        unset($data['question_image'], $data['remove_question_image']);
        if ($questionImage !== null) {
            $data['image_path'] = $this->storeQuestionImage($questionImage);
        }

        QuestionBankItem::create([
            ...$data,
            'author_id' => $faculty->id,
        ]);

        return redirect()->route('admin.question-bank.topics.show', $data['topic_id'])
            ->with('success', 'Question added to your program bank.');
    }

    public function update(Request $request, QuestionBankItem $questionBankItem): RedirectResponse
    {
        $faculty = $request->user();
        $this->ensureFacultyOwnsQuestion($faculty, $questionBankItem);
        $data = $this->validatedQuestionData($request, $faculty);
        $questionImage = $data['question_image'];
        $removeQuestionImage = $data['remove_question_image'];
        unset($data['question_image'], $data['remove_question_image']);
        if ($questionImage !== null) {
            $data['image_path'] = $this->storeQuestionImage($questionImage);
        } elseif ($removeQuestionImage) {
            $data['image_path'] = null;
        }

        $previousImagePath = $questionBankItem->image_path;
        $questionBankItem->update($data);
        $this->deleteQuestionImageWhenUnreferenced($previousImagePath, $questionBankItem->image_path);

        return redirect()->route('admin.question-bank.topics.show', $data['topic_id'])
            ->with('success', 'Question bank item updated. Existing exams remain unchanged.');
    }

    public function destroy(Request $request, QuestionBankItem $questionBankItem): RedirectResponse
    {
        $this->ensureFacultyOwnsQuestion($request->user(), $questionBankItem);
        $imagePath = $questionBankItem->image_path;
        $questionBankItem->delete();
        $this->deleteQuestionImageWhenUnreferenced($imagePath, null);

        return back()->with('success', 'Question removed from the bank. Existing exams remain unchanged.');
    }

    /**
     * @return array{course_id: int, topic_id: int, question: string, options: array<int, string>, correct_answer: string, explanation: ?string, topic: string, difficulty: string, question_image: ?UploadedFile, remove_question_image: bool}
     */
    private function validatedQuestionData(Request $request, User $faculty): array
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
            'question' => ['required', 'string', 'max:5000'],
            'options' => ['required', 'array', 'size:4'],
            'options.*' => ['required', 'string', 'max:255', 'distinct'],
            'correct_choice' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'moderate', 'hard'])],
            'question_image' => [
                'nullable',
                File::image()
                    ->max(5120)
                    ->dimensions(Rule::dimensions()->maxWidth(4096)->maxHeight(4096)),
                'mimes:jpg,jpeg,png,webp',
                'extensions:jpg,jpeg,png,webp',
            ],
            'remove_question_image' => ['sometimes', 'boolean'],
        ]);

        $this->ensureFacultyCanUseCourse($faculty, (int) $validated['course_id']);
        $topic = Topic::query()
            ->whereKey($validated['topic_id'])
            ->where('course_id', $validated['course_id'])
            ->where('is_folder', true)
            ->first();
        if ($topic === null) {
            throw ValidationException::withMessages([
                'topic_id' => 'Choose a topic from the selected program.',
            ]);
        }

        $options = array_map('trim', array_values($validated['options']));
        if (count(array_unique(array_map(fn (string $option): string => mb_strtolower($option, 'UTF-8'), $options))) !== 4) {
            throw ValidationException::withMessages([
                'options' => 'Each answer choice must be different.',
            ]);
        }
        $difficulty = $validated['difficulty'] === 'moderate' ? 'medium' : $validated['difficulty'];

        return [
            'course_id' => (int) $validated['course_id'],
            'topic_id' => $topic->id,
            'question' => trim($validated['question']),
            'options' => $options,
            'correct_answer' => $options[array_search($validated['correct_choice'], ['A', 'B', 'C', 'D'], true)],
            'explanation' => $validated['explanation'] ?? null,
            'topic' => $topic->name,
            'difficulty' => $difficulty,
            'question_image' => $request->file('question_image'),
            'remove_question_image' => $request->boolean('remove_question_image'),
        ];
    }

    private function ensureFacultyCanUseCourse(User $faculty, int $courseId): void
    {
        abort_unless(
            $faculty->role === 'admin'
                && $faculty->department_id !== null
                && Course::query()
                    ->whereKey($courseId)
                    ->where('department_id', $faculty->department_id)
                    ->exists(),
            404,
        );
    }

    private function storeQuestionImage(UploadedFile $image): string
    {
        $path = $image->store('question-bank-images', 'public');
        if (! is_string($path)) {
            throw new RuntimeException('Unable to store the question image.');
        }

        return $path;
    }

    private function deleteQuestionImageWhenUnreferenced(?string $previousPath, ?string $currentPath): void
    {
        if (
            $previousPath !== null
            && $previousPath !== $currentPath
            && ! Question::query()->where('image_path', $previousPath)->exists()
            && ! QuestionBankItem::query()->where('image_path', $previousPath)->exists()
        ) {
            Storage::disk('public')->delete($previousPath);
        }
    }

    private function ensureFacultyOwnsQuestion(User $faculty, QuestionBankItem $questionBankItem): void
    {
        abort_unless(
            $faculty->role === 'admin'
                && $faculty->department_id !== null
                && $questionBankItem->author_id === $faculty->id
                && $questionBankItem->course()->where('department_id', $faculty->department_id)->exists(),
            404,
        );
    }

    private function ensureCanFlagQuestion(User $user, QuestionBankItem $questionBankItem): void
    {
        $isQuestionAuthor = $questionBankItem->author_id !== null
            && (int) $questionBankItem->author_id === (int) $user->getKey();
        $isSuperAdmin = $user->role === 'super-admin';
        $isFacultyInSameDepartment = $user->role === 'admin'
            && $user->department_id !== null
            && $questionBankItem->course()
                ->where('department_id', $user->department_id)
                ->exists();

        abort_unless(! $isQuestionAuthor && ($isSuperAdmin || $isFacultyInSameDepartment), 404);
    }

    private function ensureFacultyOwnsTopic(User $faculty, Topic $topic): void
    {
        $this->ensureFacultyCanUseCourse($faculty, $topic->course_id);
        abort_unless($topic->is_folder && $topic->created_by === $faculty->id, 404);
    }
}
