<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMathPdfJob;
use App\Models\Module;
use App\Models\Question;
use App\Models\Result;
use App\Models\StudySession;
use App\Models\Subtopic;
use App\Models\Test;
use App\Services\AiService;
use App\Services\PdfTextExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StudentLearnController extends Controller
{
    // 2. CRITICAL: Define the property
    protected AiService $aiService;

    // 3. CRITICAL: Inject the service when the controller loads
    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $student = $request->user();

        $modules = Module::forStudent($student)
            ->when($search, function ($query, $search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->withCount('subtopics')
            ->latest()
            ->get();

        return view('student.learn.index', compact('modules', 'search'));
    }

    public function show(Module $module)
    {
        $this->ensureModuleBelongsToAuthenticatedStudent($module);

        $module->load('subtopics');

        return view('student.learn.show', compact('module'));
    }

    public function review(Subtopic $subtopic, PdfTextExtractor $extractor)
    {
        $subtopic->load('module');
        $this->ensureModuleBelongsToAuthenticatedStudent($subtopic->module);
        $this->extractPdfTextForReview($subtopic, $extractor);
        $subtopic->refresh();

        $isPdf = $this->isPdf($subtopic->file_path);
        $pdfText = match ($subtopic->text_extraction_status) {
            Subtopic::TEXT_EXTRACTION_COMPLETED => trim((string) $subtopic->extracted_text) !== ''
                ? $subtopic->extracted_text
                : 'No selectable text was found in this PDF. Please ask your faculty member to upload a text-based reviewer.',
            Subtopic::TEXT_EXTRACTION_FAILED => 'The reviewer text could not be prepared. Please contact your faculty member.',
            default => $isPdf
                ? ''
                : 'Preview is only available for PDF documents. Please download the file to view it.',
        };
        $reviewerUrl = $subtopic->file_path && Storage::disk('public')->exists($subtopic->file_path)
            ? Storage::disk('public')->url($subtopic->file_path)
            : null;

        $flashcards = auth()->user()->flashcards()
            ->where(function ($query) use ($subtopic) {
                $query->whereNull('subtopic_id')->orWhere('subtopic_id', $subtopic->id);
            })
            ->latest()
            ->get();
        $studyTimeSeconds = (int) StudySession::query()
            ->where('user_id', auth()->id())
            ->where('subtopic_id', $subtopic->id)
            ->sum('active_seconds');
        $hasTopicQuizAttempt = Result::query()
            ->where('user_id', auth()->id())
            ->whereHas('test', fn ($query) => $query
                ->where('subtopic_id', $subtopic->id)
                ->where('is_general', false))
            ->exists();

        return view('student.learn.review', compact(
            'subtopic',
            'pdfText',
            'flashcards',
            'isPdf',
            'reviewerUrl',
            'studyTimeSeconds',
            'hasTopicQuizAttempt',
        ));
    }

    public function extractionStatus(Subtopic $subtopic): JsonResponse
    {
        $subtopic->load('module');
        $this->ensureModuleBelongsToAuthenticatedStudent($subtopic->module);
        $this->queuePendingPdfExtraction($subtopic);

        return response()->json(['status' => $subtopic->fresh()->text_extraction_status]);
    }

    public function explainText(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string|max:1500',
            'context' => 'nullable|string|max:1500',
        ]);

        try {
            $explanation = $this->aiService->generateExplanation(
                $validated['text'],
                $validated['context'] ?? '',
            );

            return response()->json([
                'success' => true,
                'explanation' => $explanation,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Unable to generate a reviewer text explanation.', [
                'exception' => $exception::class,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'The AI explanation service is unavailable. Please try again shortly.',
            ], 500);
        }
    }

    /**
     * Launch or generate a 10-item quiz based on the subtopic's PDF material.
     */
    public function startQuiz(Subtopic $subtopic)
    {
        $student = auth()->user();
        $subtopic->loadMissing('module');
        $this->ensureModuleBelongsToAuthenticatedStudent($subtopic->module);

        $test = Test::firstOrCreate(
            [
                'title' => $subtopic->title.' - Review Quiz',
                'department_id' => $student->department_id,
                'course_id' => $student->course_id,
            ],
            [
                'topic' => $subtopic->title,
                'created_by' => $student->id,
                'is_general' => false,
                'question_type' => Test::QUESTION_TYPE_MULTIPLE_CHOICE,
            ]
        );

        // 2. If the test doesn't have questions yet, generate them with AI
        if ($test->questions()->count() < 10) {
            $pdfText = $subtopic->title;

            if ($this->isPdf($subtopic->file_path)) {
                $this->queuePendingPdfExtraction($subtopic);
                $subtopic->refresh();

                if ($subtopic->text_extraction_status !== Subtopic::TEXT_EXTRACTION_COMPLETED) {
                    return back()->withErrors([
                        'quiz' => 'The reviewer text is not ready yet. Please wait for extraction to finish, then try again.',
                    ]);
                }

                $pdfText = mb_substr((string) $subtopic->extracted_text, 0, 12000);
            }

            try {
                // Generate 10 multiple-choice questions from the PDF text
                $generatedQuestions = $this->aiService->generateGeneralTest($pdfText, 10);

                // Clear any incomplete sets and insert fresh questions
                $test->questions()->delete();

                foreach ($generatedQuestions as $q) {
                    Question::create([
                        'test_id' => $test->id,
                        'question' => $q['question'],
                        'options' => $q['options'], // Let the model's $casts handle it automatically
                        'correct_answer' => $q['correct_answer'],
                        'explanation' => $q['explanation'] ?? null,
                    ]);
                }
            } catch (\Exception $e) {
                return back()->withErrors(['error' => 'Failed to generate quiz: '.$e->getMessage()]);
            }
        }

        // 3. Load questions and display your existing take-test view
        $test->load('questions');

        return view('student.take-test', compact('test'));
    }

    public function submit(Request $request, Test $test)
    {
        $submittedAnswers = $request->input('answers', []);
        $score = 0;
        $quizSnapshot = [];

        foreach ($test->questions as $question) {
            $userAns = isset($submittedAnswers[$question->id]) ? trim($submittedAnswers[$question->id]) : null;
            $rawCorrect = trim($question->correct_answer);

            $options = is_string($question->options) ? json_decode($question->options, true) : $question->options;
            $actualCorrect = $rawCorrect;

            if (is_array($options)) {
                foreach ($options as $optIndex => $opt) {
                    $cleanOpt = trim($opt);
                    if (strcasecmp($cleanOpt, $rawCorrect) === 0 || strtoupper($rawCorrect) === chr(65 + $optIndex)) {
                        $actualCorrect = $cleanOpt;
                        break;
                    }
                }
            }

            $isCorrect = false;
            if ($userAns !== null && strcasecmp($userAns, $actualCorrect) === 0) {
                $score++;
                $isCorrect = true;
            }

            // Build the snapshot for EACH question
            $quizSnapshot[] = [
                'question_text' => preg_replace('/^(Q\d+:?\s*)+/', '', $question->question),
                'options' => is_array($options) ? $options : [],
                'correct_answer' => $actualCorrect,
                'user_answer' => $userAns,
                'is_correct' => $isCorrect,
                'explanation' => $question->explanation,
            ];
        }

        // Save it securely bypassing any background AI events
        $result = Result::withoutEvents(function () use ($test, $score, $quizSnapshot) {
            $newResult = new Result;
            $newResult->user_id = auth()->id();
            $newResult->test_id = $test->id;
            $newResult->score = $score;

            // BULLETPROOF FIX: Manually encode the array into a raw JSON string.
            $newResult->answers = json_encode($quizSnapshot);

            $newResult->save();

            return $newResult;
        });

        return redirect()->route('student.results.show', $result->id);
    }

    private function ensureModuleBelongsToAuthenticatedStudent(Module $module): void
    {
        abort_unless(
            Module::forStudent(auth()->user())->whereKey($module->getKey())->exists(),
            404,
        );
    }

    private function extractPdfTextForReview(Subtopic $subtopic, PdfTextExtractor $extractor): void
    {
        $filePath = $subtopic->file_path;
        if (! $this->isPdf($filePath)
            || ! $filePath
            || $subtopic->text_extraction_status === Subtopic::TEXT_EXTRACTION_COMPLETED) {
            return;
        }

        if (! Storage::disk('public')->exists($filePath)) {
            Subtopic::query()
                ->whereKey($subtopic->id)
                ->where('file_path', $filePath)
                ->update([
                    'extracted_text' => null,
                    'text_extraction_status' => Subtopic::TEXT_EXTRACTION_FAILED,
                ]);

            Log::warning('Reviewer PDF is missing from public storage.', [
                'subtopic_id' => $subtopic->id,
                'file_path' => $subtopic->file_path,
            ]);

            return;
        }

        $updated = Subtopic::query()
            ->whereKey($subtopic->id)
            ->where('file_path', $filePath)
            ->update([
                'extracted_text' => null,
                'text_extraction_status' => Subtopic::TEXT_EXTRACTION_PROCESSING,
            ]);
        if ($updated === 0) {
            return;
        }

        try {
            $extractedText = $extractor->extract(Storage::disk('public')->path($filePath));
        } catch (\RuntimeException $exception) {
            Subtopic::query()
                ->whereKey($subtopic->id)
                ->where('file_path', $filePath)
                ->update(['text_extraction_status' => Subtopic::TEXT_EXTRACTION_FAILED]);

            Log::error('Immediate PyMuPDF reviewer extraction failed.', [
                'subtopic_id' => $subtopic->id,
                'file_path' => $filePath,
                'exception' => $exception,
            ]);

            return;
        }

        Subtopic::query()
            ->whereKey($subtopic->id)
            ->where('file_path', $filePath)
            ->update([
                'extracted_text' => $extractedText,
                'text_extraction_status' => Subtopic::TEXT_EXTRACTION_COMPLETED,
            ]);
    }

    private function queuePendingPdfExtraction(Subtopic $subtopic): void
    {
        if (! $this->isPdf($subtopic->file_path) || ! $subtopic->file_path) {
            return;
        }

        if (! Storage::disk('public')->exists($subtopic->file_path)) {
            if ($subtopic->text_extraction_status !== Subtopic::TEXT_EXTRACTION_COMPLETED
                && $subtopic->text_extraction_status !== Subtopic::TEXT_EXTRACTION_FAILED) {
                $subtopic->update(['text_extraction_status' => Subtopic::TEXT_EXTRACTION_FAILED]);
            }

            return;
        }

        if ($subtopic->text_extraction_status === Subtopic::TEXT_EXTRACTION_NOT_REQUIRED) {
            $subtopic->update(['text_extraction_status' => Subtopic::TEXT_EXTRACTION_PENDING]);
        }

        if ($subtopic->text_extraction_status === Subtopic::TEXT_EXTRACTION_PENDING) {
            ProcessMathPdfJob::dispatch($subtopic->id, $subtopic->file_path);
        }
    }

    private function isPdf(?string $filePath): bool
    {
        return $filePath !== null && strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf';
    }
}
