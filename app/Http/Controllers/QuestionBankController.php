<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionBankItem;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class QuestionBankController extends Controller
{
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
    private function questionBankData(Request $request): array
    {
        $faculty = $request->user();
        $validated = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard'])],
        ]);
        $courses = Course::query()
            ->where('department_id', $faculty->department_id)
            ->orderBy('name')
            ->get(['id', 'name']);
        $selectedCourseId = isset($validated['course_id'])
            ? (int) $validated['course_id']
            : (! $request->query->has('course_id') && $courses->count() === 1 ? $courses->first()->id : null);
        abort_if($selectedCourseId !== null && ! $courses->contains('id', $selectedCourseId), 404);
        $selectedTopicId = isset($validated['topic_id']) ? (int) $validated['topic_id'] : null;
        $selectedDifficulty = $validated['difficulty'] ?? null;
        abort_if($selectedTopicId !== null && $selectedCourseId === null, 404);

        $topics = $selectedCourseId === null
            ? collect()
            : Topic::query()
                ->where('course_id', $selectedCourseId ?? 0)
                ->where('is_folder', true)
                ->withCount([
                    'bankItems as questions_count' => fn ($query) => $query->where('author_id', $faculty->id),
                ])
                ->orderBy('name')
                ->get();
        $selectedTopic = $selectedTopicId === null
            ? null
            : $topics->firstWhere('id', $selectedTopicId);
        abort_if($selectedTopicId !== null && $selectedTopic === null, 404);

        $itemsQuery = QuestionBankItem::query()
            ->where('author_id', $faculty->id)
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
        $items = $itemsQuery->latest()->paginate(20)->withQueryString();

        return [
            'items' => $items,
            'courses' => $courses,
            'course' => $courses->firstWhere('id', $selectedCourseId),
            'selectedCourseId' => $selectedCourseId,
            'topics' => $topics,
            'selectedTopic' => $selectedTopic,
            'selectedDifficulty' => $selectedDifficulty,
            'readOnly' => false,
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
        $this->ensureFacultyCanUseCourse($request->user(), $topic->course_id);
        abort_unless($topic->is_folder, 404);
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
        $this->ensureFacultyCanUseCourse($request->user(), $topic->course_id);
        abort_unless($topic->is_folder, 404);
        $courseId = $topic->course_id;
        $imagePaths = DB::transaction(function () use ($topic): array {
            Topic::query()->whereKey($topic->id)->lockForUpdate()->firstOrFail();
            $bankItems = $topic->bankItems()
                ->lockForUpdate()
                ->get(['id', 'image_path']);

            $topic->bankItems()->delete();
            $topic->delete();

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
        $this->ensureFacultyOwnsQuestion($request->user(), $questionBankItem);
        abort_if($questionBankItem->topic_id === null, 404);
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $questionBankItem->update(['is_active' => (bool) $validated['is_active']]);

        return redirect()->route('admin.question-bank.topics.show', $questionBankItem->topic_id)
            ->with('success', $questionBankItem->is_active ? 'Question enabled for future exams.' : 'Question disabled for future exams.');
    }

    public function adminIndex(Request $request): View
    {
        $validated = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $selectedFaculty = null;
        if (isset($validated['author_id'])) {
            $selectedFaculty = User::query()
                ->with('department:id,name')
                ->where('role', 'admin')
                ->findOrFail($validated['author_id']);
        } elseif (isset($validated['course_id'])) {
            abort(404);
        }

        $courses = $selectedFaculty?->department_id === null
            ? collect()
            : Course::query()
                ->where('department_id', $selectedFaculty->department_id)
                ->with('department:id,name')
                ->orderBy('name')
                ->get();
        $faculties = User::query()
            ->where('role', 'admin')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'department_id']);
        abort_if(isset($validated['course_id']) && ! $courses->contains('id', (int) $validated['course_id']), 404);
        $items = QuestionBankItem::query()
            ->with(['course:id,name,department_id', 'course.department:id,name', 'author:id,name', 'subtopic:id,title'])
            ->when(
                isset($validated['course_id']),
                fn ($query) => $query->where('course_id', $validated['course_id']),
            )
            ->when(isset($validated['author_id']), fn ($query) => $query->where('author_id', $validated['author_id']))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('super-admin.question-bank.index', [
            'items' => $items,
            'courses' => $courses,
            'faculties' => $faculties,
            'selectedFaculty' => $selectedFaculty,
            'selectedCourseId' => $validated['course_id'] ?? null,
            'selectedAuthorId' => $validated['author_id'] ?? null,
            'readOnly' => true,
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
                && $questionBankItem->author_id === $faculty->id
                && $questionBankItem->course()->where('department_id', $faculty->department_id)->exists(),
            404,
        );
    }
}
