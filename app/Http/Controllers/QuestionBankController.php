<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\QuestionBankItem;
use App\Models\Subtopic;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuestionBankController extends Controller
{
    public function index(): View
    {
        $faculty = auth()->user();
        $courses = Course::query()
            ->where('department_id', $faculty->department_id)
            ->orderBy('name')
            ->get(['id', 'name']);
        $selectedCourseId = request()->integer('course_id') ?: ($courses->count() === 1 ? $courses->first()->id : null);
        abort_if($selectedCourseId !== null && ! $courses->contains('id', $selectedCourseId), 404);
        $items = QuestionBankItem::query()
            ->where('author_id', $faculty->id)
            ->when($selectedCourseId !== null, fn ($query) => $query->where('course_id', $selectedCourseId))
            ->with(['author:id,name', 'course:id,name', 'subtopic:id,module_id,title', 'subtopic.module:id,title'])
            ->latest()
            ->paginate(20);
        $subtopics = Subtopic::query()
            ->whereHas('module', fn ($query) => $query->whereIn('course_id', $courses->pluck('id')))
            ->with('module:id,title,course_id')
            ->orderBy('title')
            ->get(['id', 'module_id', 'title']);

        return view('admin.question-bank.index', [
            'items' => $items,
            'subtopics' => $subtopics,
            'courses' => $courses,
            'course' => $courses->firstWhere('id', $selectedCourseId),
            'selectedCourseId' => $selectedCourseId,
            'readOnly' => false,
        ]);
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

        QuestionBankItem::create([
            ...$data,
            'author_id' => $faculty->id,
        ]);

        return redirect()->route('admin.question-bank.index', ['course_id' => $data['course_id']])
            ->with('success', 'Question added to your program bank.');
    }

    public function update(Request $request, QuestionBankItem $questionBankItem): RedirectResponse
    {
        $faculty = $request->user();
        $this->ensureFacultyOwnsQuestion($faculty, $questionBankItem);
        $data = $this->validatedQuestionData($request, $faculty);

        $questionBankItem->update($data);

        return redirect()->route('admin.question-bank.index', ['course_id' => $data['course_id']])
            ->with('success', 'Question bank item updated. Existing exams remain unchanged.');
    }

    public function destroy(Request $request, QuestionBankItem $questionBankItem): RedirectResponse
    {
        $this->ensureFacultyOwnsQuestion($request->user(), $questionBankItem);
        $questionBankItem->delete();

        return back()->with('success', 'Question removed from the bank. Existing exams remain unchanged.');
    }

    /**
     * @return array{course_id: int, question: string, options: array<int, string>, correct_answer: string, explanation: ?string, topic: string, subtopic_id: ?int}
     */
    private function validatedQuestionData(Request $request, User $faculty): array
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'question' => ['required', 'string', 'max:5000'],
            'options' => ['required', 'array', 'size:4'],
            'options.*' => ['required', 'string', 'max:255', 'distinct'],
            'correct_option' => ['required', 'integer', 'between:0,3'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'topic' => ['required', 'string', 'max:255'],
            'subtopic_id' => ['nullable', 'integer', 'exists:subtopics,id'],
        ]);

        $courseBelongsToCollege = Course::query()
            ->whereKey($validated['course_id'])
            ->where('department_id', $faculty->department_id)
            ->exists();
        if (! $courseBelongsToCollege) {
            throw ValidationException::withMessages([
                'course_id' => 'Choose a program from your assigned college.',
            ]);
        }

        $options = array_map('trim', array_values($validated['options']));
        if (isset($validated['subtopic_id'])) {
            $subtopicBelongsToCourse = Subtopic::query()
                ->whereKey($validated['subtopic_id'])
                ->whereHas('module', fn ($query) => $query->where('course_id', $validated['course_id']))
                ->exists();
            if (! $subtopicBelongsToCourse) {
                throw ValidationException::withMessages([
                    'subtopic_id' => 'Choose a module topic from the selected program.',
                ]);
            }
        }

        return [
            'course_id' => (int) $validated['course_id'],
            'question' => $validated['question'],
            'options' => $options,
            'correct_answer' => $options[(int) $validated['correct_option']],
            'explanation' => $validated['explanation'] ?? null,
            'topic' => $validated['topic'],
            'subtopic_id' => $validated['subtopic_id'] ?? null,
        ];
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
