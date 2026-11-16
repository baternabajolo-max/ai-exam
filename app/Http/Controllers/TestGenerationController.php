<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\QuestionBankItem;
use App\Services\AiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TestGenerationController extends Controller
{
    public function __construct(private AiService $aiService) {}

    public function store(Request $request): RedirectResponse
    {
        $faculty = $request->user();
        abort_unless($faculty->role === 'admin', 403);

        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'topic' => ['required', 'string', 'max:255'],
            'question_count' => ['required', 'integer', 'min:1', 'max:20'],
            'difficulty' => ['required', 'string', 'in:easy,medium,hard'],
        ]);
        abort_unless(
            Course::query()
                ->whereKey($validated['course_id'])
                ->where('department_id', $faculty->department_id)
                ->exists(),
            404,
        );

        try {
            $questions = $this->aiService->generateTestQuestions(
                $validated['topic'],
                (int) $validated['question_count'],
                $validated['difficulty'],
            );
            if (count($questions) !== (int) $validated['question_count']) {
                throw new RuntimeException('The question generator returned an incomplete set.');
            }

            DB::transaction(function () use ($questions, $validated, $faculty): void {
                foreach ($questions as $question) {
                    $options = array_values($question['options'] ?? []);
                    $correctAnswer = trim((string) ($question['correct_answer'] ?? ''));
                    if (preg_match('/^[A-D]$/i', $correctAnswer) === 1) {
                        $correctAnswer = (string) ($options[ord(strtoupper($correctAnswer)) - 65] ?? '');
                    }

                    if (trim((string) ($question['question'] ?? '')) === ''
                        || count($options) !== 4
                        || $correctAnswer === ''
                        || ! in_array($correctAnswer, $options, true)) {
                        throw new RuntimeException('The question generator returned an invalid question.');
                    }

                    QuestionBankItem::create([
                        'course_id' => $validated['course_id'],
                        'author_id' => $faculty->id,
                        'question' => $question['question'],
                        'options' => $options,
                        'correct_answer' => $correctAnswer,
                        'explanation' => $question['explanation'] ?? null,
                        'topic' => $validated['topic'],
                    ]);
                }
            });
        } catch (RuntimeException $exception) {
            report($exception);

            return back()->withErrors([
                'generation' => 'Question generation failed. Please try again.',
            ]);
        }

        return redirect()
            ->route('admin.question-bank.index', ['course_id' => $validated['course_id']])
            ->with('success', 'AI-generated questions were added to the Question Bank. Create an examination to schedule them.');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.tests.create');
    }
}
