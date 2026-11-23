<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMathPdfJob;
use App\Models\Module;
use App\Models\Question;
use App\Models\Result;
use App\Models\Reviewer;
use App\Models\Subtopic;
use App\Models\Test;
use App\Models\TestRetakeGrant;
use App\Models\User;
use App\Services\AchievementUnlockService;
use App\Services\AiService;
use App\Services\AprioriTopicMiner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class StudentTestController extends Controller
{
    protected AiService $aiService;

    public function __construct(
        AiService $aiService,
        private AprioriTopicMiner $aprioriTopicMiner,
    ) {
        $this->aiService = $aiService;
    }

    public function index()
    {
        $results = Result::where('user_id', auth()->id())->with('test')->latest()->paginate(10);

        return view('student.results.index', compact('results'));
    }

    public function exams()
    {
        $student = auth()->user();
        $exams = Test::query()
            ->where('department_id', $student->department_id)
            ->where('course_id', $student->course_id)
            ->where('is_scheduled_exam', true)
            ->where('approval_status', Test::APPROVAL_STATUS_APPROVED)
            ->where('is_published', true)
            ->with([
                'course:id,name',
                'results' => fn ($query) => $query
                    ->where('user_id', $student->id)
                    ->latest('created_at')
                    ->latest('id')
                    ->limit(1),
                'retakeGrants' => fn ($query) => $query
                    ->where('student_id', $student->id)
                    ->whereNull('consumed_at'),
            ])
            ->orderBy('available_from')
            ->get();
        $unsubmittedExams = $exams
            ->filter(fn (Test $exam): bool => $exam->results->isEmpty());
        $now = now();
        $missedExams = $unsubmittedExams
            ->filter(fn (Test $exam): bool => $exam->available_until?->isBefore($now) === true)
            ->values();
        $availableExams = $unsubmittedExams
            ->reject(fn (Test $exam): bool => $exam->available_until?->isBefore($now) === true)
            ->values();
        $takenExams = $exams
            ->filter(fn (Test $exam): bool => $exam->results->isNotEmpty())
            ->sortByDesc(fn (Test $exam) => $exam->results->first()?->created_at)
            ->values();

        return view('student.exams.index', compact('availableExams', 'missedExams', 'takenExams'));
    }

    public function startExam(Test $test)
    {
        $completedResult = $this->ensureStudentCanAccessTest($test);
        if ($completedResult !== null) {
            return redirect()->route('student.results.show', $completedResult);
        }

        abort_unless($test->is_scheduled_exam, 404);

        $retakeGrant = $this->startActiveExamRetake($test);
        $timeLimitSeconds = $this->timeLimitSeconds($test);
        if ($retakeGrant === null && $timeLimitSeconds !== null && session()->get($this->examTimerSessionKey($test)) === null) {
            session()->put($this->examTimerSessionKey($test), now()->timestamp);
        }

        return redirect()->route('student.tests.show', $test);
    }

    public function generateAndTakeGeneral()
    {
        $user = auth()->user();
        $hasSubtopics = Module::forStudent($user)
            ->whereHas('subtopics')
            ->exists();

        if (! $hasSubtopics) {
            return back()->with('error', 'There are no learning modules yet. Please try again after your faculty member adds a module and subtopic.');
        }

        $test = Test::create([
            'department_id' => $user->department_id,
            'course_id' => $user->course_id,
            'created_by' => $user->id,
            'title' => 'General Assessment - '.now()->format('Y-m-d H:i'),
            'is_general' => true,
            'question_type' => Test::QUESTION_TYPE_MULTIPLE_CHOICE,
            'time_limit' => null,
        ]);

        return redirect()->route('student.tests.show', $test->id);
    }

    public function generateTargetedTest(Request $request, ?string $topic = null)
    {
        $validated = $request->validate([
            'question_type' => ['sometimes', 'in:multiple_choice,identification'],
        ]);
        $user = auth()->user();
        $decodedTopic = $topic ? urldecode($topic) : 'Weak Area Remediation';

        $test = Test::create([
            'department_id' => $user->department_id,
            'course_id' => $user->course_id,
            'created_by' => $user->id,
            'title' => 'Weak Area Remediation: '.$decodedTopic,
            'is_general' => false,
            'topic' => $decodedTopic,
            'question_type' => $validated['question_type'] ?? Test::QUESTION_TYPE_MULTIPLE_CHOICE,
            'time_limit' => null,
        ]);

        return redirect()->to(route('student.tests.show', ['test' => $test->id]));
    }

    public function take(Test $test)
    {
        $completedResult = $this->ensureStudentCanAccessTest($test);
        if ($completedResult !== null) {
            return redirect()->route('student.results.show', $completedResult);
        }

        $retakeGrant = $test->is_scheduled_exam ? $this->startActiveExamRetake($test) : null;
        $timeLimitSeconds = $this->timeLimitSeconds($test);
        $remainingSeconds = null;
        if ($timeLimitSeconds !== null) {
            $startedAt = $retakeGrant?->started_at?->timestamp
                ?? session()->get($this->examTimerSessionKey($test));
            if ($startedAt === null) {
                $startedAt = now()->timestamp;
                session()->put($this->examTimerSessionKey($test), $startedAt);
            }
            $remainingSeconds = max(0, $timeLimitSeconds - (now()->timestamp - (int) $startedAt));
        }

        $resultId = session('completed_result_id');
        if ($resultId) {
            $result = Result::where('id', $resultId)
                ->where('user_id', auth()->id())
                ->where('test_id', $test->id)
                ->first();

            if ($result) {
                return view('student.take-test', compact('test'));
            }
        }

        if ($test->is_general) {
            $test->update(['question_type' => Test::QUESTION_TYPE_MULTIPLE_CHOICE]);

            if (! $this->hasCompleteGeneralQuestionSet($test)) {
                $test->questions()->delete();
            }
        }

        $test->load('questions');

        return view('student.tests.take', compact('test', 'remainingSeconds'));
    }

    public function show(Test $test)
    {
        return $this->take($test);
    }

    public function buildQuestions(Test $test)
    {
        $student = auth()->user();
        if ($test->department_id !== $student->department_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized department context.'], 403);
        }

        if ($test->is_scheduled_exam) {
            if ($student->course_id === null || $test->course_id !== $student->course_id) {
                abort(404);
            }
            $completedResult = $this->findCompletedScheduledExamResult($test);
            if ($completedResult !== null && $this->activeExamRetakeGrant($test) === null) {
                return response()->json([
                    'status' => 'already_completed',
                    'result_url' => route('student.results.show', $completedResult),
                ], 409);
            }
            if (! $test->isAvailableAt()) {
                return response()->json(['status' => 'error', 'message' => 'This examination is not currently available.'], 403);
            }

            return $test->questions()->exists()
                ? response()->json(['status' => 'already_generated'])
                : response()->json(['status' => 'error', 'message' => 'This scheduled examination has no questions. Contact your Faculty member.'], 409);
        }

        if ($test->is_general) {
            $test->update(['question_type' => Test::QUESTION_TYPE_MULTIPLE_CHOICE]);
        }

        $expectedQuestionCount = $test->is_general ? 30 : 20;
        $questionsAreReady = $test->is_general
            ? $this->hasCompleteGeneralQuestionSet($test)
            : $test->questions()->count() === $expectedQuestionCount;

        if ($questionsAreReady) {
            return response()->json(['status' => 'already_generated']);
        }

        $user = auth()->user();
        $modules = Module::forStudent($user)
            ->with('subtopics')
            ->get();
        $moduleContext = '';

        foreach ($modules as $module) {
            $moduleDescription = trim((string) $module->description);

            if ($moduleDescription !== '') {
                $moduleContext .= "Module: {$module->title}\nDescription: {$moduleDescription}\n";
            }

            foreach ($module->subtopics as $subtopic) {
                $subtopicDescription = trim((string) $subtopic->description);
                $reviewerText = $subtopic->file_path
                    ? trim($this->extractSubtopicStudyText($subtopic))
                    : '';

                if ($subtopicDescription === '' && $reviewerText === '') {
                    continue;
                }

                $moduleContext .= "Module: {$module->title}\nSubtopic: {$subtopic->title}\n";

                if ($subtopicDescription !== '') {
                    $moduleContext .= "Description: {$subtopicDescription}\n";
                }

                if ($reviewerText !== '') {
                    $moduleContext .= $reviewerText."\n";
                }
            }
        }

        $reviewerContext = '';
        foreach (Reviewer::where('department_id', $user->department_id)->get() as $reviewer) {
            if ($reviewer->file_path) {
                $reviewerContext .= $this->extractStudyText($reviewer->file_path);
            }
        }

        $context = $test->is_general ? $moduleContext : $reviewerContext;
        if (empty(trim($context))) {
            $reviewerExtractionPending = $modules
                ->flatMap(fn (Module $module) => $module->subtopics)
                ->contains(fn (Subtopic $subtopic): bool => in_array(
                    $subtopic->text_extraction_status,
                    [Subtopic::TEXT_EXTRACTION_PENDING, Subtopic::TEXT_EXTRACTION_PROCESSING],
                    true,
                ));

            return response()->json([
                'status' => 'error',
                'message' => $reviewerExtractionPending
                    ? 'Reviewer text is being prepared. Please try generating the test again in a moment.'
                    : 'There is no usable study material for a general test yet. Ask your faculty member to add module descriptions or upload accessible subtopic reviewer files, then try again.',
            ], 422);
        }

        try {
            $previousQuestions = $this->previousQuestionsFor(
                $test->is_general,
                $test->department_id,
                $test->course_id,
                $test->subtopic_id,
                $test->topic,
                $user->id,
            );

            if ($test->is_general) {
                $weakTopics = $this->weakTopicsFor($user);
                $generatedQuestions = collect($this->aiService->generateGeneralTest(
                    $context,
                    30,
                    Test::QUESTION_TYPE_MULTIPLE_CHOICE,
                    $previousQuestions,
                    $weakTopics,
                ))->map(fn (array $question): array => [
                    ...$question,
                    'question_type' => Test::QUESTION_TYPE_MULTIPLE_CHOICE,
                ]);
                if ($generatedQuestions->count() !== 30) {
                    throw new \RuntimeException('The AI returned an incomplete general test. Please try again.');
                }
                if ($weakTopics !== []) {
                    $allowedTopics = array_map(
                        static fn (array $weakTopic): string => mb_strtolower(trim($weakTopic['topic'])),
                        $weakTopics,
                    );
                    $hasOffTopicQuestion = $generatedQuestions->contains(
                        static fn (array $question): bool => ! in_array(
                            mb_strtolower(trim((string) ($question['topic'] ?? ''))),
                            $allowedTopics,
                            true,
                        ),
                    );
                    if ($hasOffTopicQuestion) {
                        throw new \RuntimeException('The generated questions did not stay within the student’s weak topics. Please try again.');
                    }
                }
            } else {
                $generatedQuestions = collect($this->aiService->generateTargetedTest(
                    $context,
                    $test->topic,
                    20,
                    $test->question_type,
                    $previousQuestions,
                ))->map(fn (array $question): array => [
                    ...$question,
                    'question_type' => $test->question_type,
                ]);
                if ($generatedQuestions->count() !== 20) {
                    throw new \RuntimeException('The AI returned an incomplete test. Please try again.');
                }
            }

            DB::transaction(function () use ($test, $generatedQuestions): void {
                $test->questions()->delete();

                foreach ($generatedQuestions as $questionData) {
                    $options = array_values($questionData['options'] ?? []);
                    $correctAnswer = trim($questionData['correct_answer']);

                    $questionType = $questionData['question_type'] ?? $test->question_type;

                    if ($questionType === Test::QUESTION_TYPE_IDENTIFICATION) {
                        $correctAnswer = mb_strtoupper($correctAnswer);
                    } elseif ($test->is_general && preg_match('/^[A-D]$/i', $correctAnswer)) {
                        $correctAnswer = $options[ord(strtoupper($correctAnswer)) - 65] ?? $correctAnswer;
                    }

                    if ($questionType === Test::QUESTION_TYPE_MULTIPLE_CHOICE && ! $test->is_general) {
                        $options = collect($options)->shuffle()->values()->all();
                    }

                    Question::create([
                        'test_id' => $test->id,
                        'question_type' => $questionType,
                        'question' => $questionData['question'],
                        'options' => $options,
                        'correct_answer' => $correctAnswer,
                        'explanation' => $questionData['explanation'] ?? '',
                        'topic' => $questionData['topic'] ?? $test->topic,
                    ]);
                }
            });

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }

    }

    private function hasCompleteGeneralQuestionSet(Test $test): bool
    {
        $questions = $test->questions()->get();

        return $questions->count() === 30
            && $questions->every(fn (Question $question): bool => ($question->question_type ?? Test::QUESTION_TYPE_MULTIPLE_CHOICE) === Test::QUESTION_TYPE_MULTIPLE_CHOICE
                && count($question->options ?? []) === 4);
    }

    /**
     * @return array<int, array{question_text: string, options: array<int, string>}>
     */
    private function previousQuestionsFor(
        bool $isGeneral,
        int $departmentId,
        ?int $courseId,
        ?int $subtopicId,
        ?string $topic,
        int $userId,
    ): array {
        $results = Result::query()
            ->where('user_id', $userId)
            ->whereHas('test', function ($query) use ($isGeneral, $departmentId, $courseId, $subtopicId, $topic): void {
                $query->where('is_general', $isGeneral);

                if ($isGeneral) {
                    $query->where('department_id', $departmentId)
                        ->where('course_id', $courseId);
                } elseif ($subtopicId !== null) {
                    $query->where(function ($subtopicQuery) use ($subtopicId, $topic): void {
                        $subtopicQuery->where('subtopic_id', $subtopicId)
                            ->orWhere(function ($legacyQuery) use ($topic): void {
                                $legacyQuery->whereNull('subtopic_id')
                                    ->where('topic', $topic);
                            });
                    });
                } else {
                    $query->where('topic', $topic);
                }
            })
            ->latest()
            ->take(5)
            ->get(['answers']);

        return $results
            ->flatMap(fn (Result $result): array => $this->resultAnswers($result))
            ->take(30)
            ->filter(static fn (mixed $answer): bool => is_array($answer))
            ->map(static fn (array $answer): array => [
                'question_text' => $answer['question_text'] ?? '',
                'options' => $answer['options'] ?? [],
                'topic' => $answer['topic'] ?? null,
                'correct_answer' => $answer['correct_answer'] ?? null,
            ])
            ->filter(static fn (array $answer): bool => $answer['question_text'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resultAnswers(Result $result): array
    {
        $answers = $result->answers;

        while (is_string($answers)) {
            $answers = json_decode($answers, true);
        }

        return is_array($answers)
            ? array_values(array_filter($answers, 'is_array'))
            : [];
    }

    /**
     * @return array<int, array{topic: string, incorrect_answers: int, total_questions: int, accuracy: int, repeated_in_attempts: int}>
     */
    private function weakTopicsFor(User $user): array
    {
        $results = Result::query()
            ->where('user_id', $user->id)
            ->whereHas('test', fn ($query) => $query
                ->where('department_id', $user->department_id)
                ->where('course_id', $user->course_id)
                ->where('is_general', true))
            ->with(['test.questions:id,test_id,question,topic'])
            ->latest()
            ->latest('id')
            ->take(10)
            ->get();
        $topicStats = [];
        $topicTransactions = [];

        foreach ($results as $result) {
            $test = $result->test;
            if (! $test) {
                continue;
            }

            $questionTopics = $test->questions->mapWithKeys(fn (Question $question): array => [
                $this->normalizeQuestionText($question->question) => $question->topic,
            ]);
            $resultTopicStats = [];

            foreach ($this->resultAnswers($result) as $answer) {
                $questionText = (string) ($answer['question_text'] ?? '');
                $topic = trim((string) (
                    $answer['topic']
                    ?? $questionTopics->get($this->normalizeQuestionText($questionText))
                    ?? ($test->is_general ? '' : $test->topic)
                    ?? ''
                ));

                if ($topic === '' || mb_strtolower($topic) === 'general') {
                    continue;
                }

                $topicKey = mb_strtolower($topic);
                $resultTopicStats[$topicKey] ??= [
                    'topic' => $topic,
                    'total_questions' => 0,
                    'incorrect_answers' => 0,
                ];
                $resultTopicStats[$topicKey]['total_questions']++;

                if (! ($answer['is_correct'] ?? false)) {
                    $resultTopicStats[$topicKey]['incorrect_answers']++;
                }
            }

            $weakTopicsInAttempt = [];
            foreach ($resultTopicStats as $topicKey => $resultTopicStat) {
                $topicStats[$topicKey] ??= [
                    'topic' => $resultTopicStat['topic'],
                    'total_questions' => 0,
                    'incorrect_answers' => 0,
                ];
                $topicStats[$topicKey]['total_questions'] += $resultTopicStat['total_questions'];
                $topicStats[$topicKey]['incorrect_answers'] += $resultTopicStat['incorrect_answers'];

                $attemptAccuracy = ($resultTopicStat['total_questions'] - $resultTopicStat['incorrect_answers'])
                    / $resultTopicStat['total_questions'];
                if ($attemptAccuracy < 0.6) {
                    $weakTopicsInAttempt[] = $topicKey;
                }
            }
            $topicTransactions[] = $weakTopicsInAttempt;
        }

        $frequentTopics = $this->aprioriTopicMiner->mine($topicTransactions, 2)['topic_support'];
        $weakTopics = [];

        foreach ($topicStats as $topicKey => $topicStat) {
            $repeatedInAttempts = $frequentTopics[$topicKey] ?? 0;
            if ($repeatedInAttempts < 2
                || $topicStat['total_questions'] < 2
                || ($topicStat['total_questions'] - $topicStat['incorrect_answers']) / $topicStat['total_questions'] >= 0.6) {
                continue;
            }

            $weakTopics[] = [
                'topic' => $topicStat['topic'],
                'incorrect_answers' => $topicStat['incorrect_answers'],
                'total_questions' => $topicStat['total_questions'],
                'accuracy' => (int) round(
                    (($topicStat['total_questions'] - $topicStat['incorrect_answers']) / $topicStat['total_questions']) * 100,
                ),
                'repeated_in_attempts' => $repeatedInAttempts,
            ];
        }

        usort($weakTopics, static fn (array $left, array $right): int => $right['repeated_in_attempts'] <=> $left['repeated_in_attempts']
            ?: $right['incorrect_answers'] <=> $left['incorrect_answers']
            ?: $left['accuracy'] <=> $right['accuracy']);

        return array_slice($weakTopics, 0, 5);
    }

    private function normalizeQuestionText(string $text): string
    {
        return preg_replace('/[^\pL\pN]+/u', '', mb_strtolower(trim($text))) ?? '';
    }

    private function extractStudyText(string $filePath): string
    {
        if (! Storage::disk('public')->exists($filePath)) {
            return '';
        }

        $fullPath = storage_path('app/public/'.$filePath);
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        if ($extension === 'txt') {
            return file_get_contents($fullPath)."\n";
        }

        if ($extension === 'pdf' && class_exists(Parser::class)) {
            try {
                return (new Parser)->parseFile($fullPath)->getText()."\n";
            } catch (\Exception) {
                return '';
            }
        }

        return '';
    }

    private function extractSubtopicStudyText(Subtopic $subtopic): string
    {
        if (strtolower(pathinfo((string) $subtopic->file_path, PATHINFO_EXTENSION)) !== 'pdf') {
            return $subtopic->file_path ? $this->extractStudyText($subtopic->file_path) : '';
        }

        if (! Storage::disk('public')->exists($subtopic->file_path)) {
            if ($subtopic->text_extraction_status !== Subtopic::TEXT_EXTRACTION_COMPLETED
                && $subtopic->text_extraction_status !== Subtopic::TEXT_EXTRACTION_FAILED) {
                $subtopic->update(['text_extraction_status' => Subtopic::TEXT_EXTRACTION_FAILED]);
            }

            return '';
        }

        if ($subtopic->text_extraction_status === Subtopic::TEXT_EXTRACTION_NOT_REQUIRED) {
            $subtopic->update(['text_extraction_status' => Subtopic::TEXT_EXTRACTION_PENDING]);
        }

        if ($subtopic->text_extraction_status === Subtopic::TEXT_EXTRACTION_PENDING) {
            ProcessMathPdfJob::dispatch($subtopic->id, $subtopic->file_path);
        }

        return $subtopic->text_extraction_status === Subtopic::TEXT_EXTRACTION_COMPLETED
            ? (string) $subtopic->extracted_text."\n"
            : '';
    }

    public function submit(
        Request $request,
        Test $test,
        AchievementUnlockService $achievementUnlockService,
    ) {
        $completedResult = $this->ensureStudentCanAccessTest($test);
        if ($completedResult !== null) {
            return redirect()->route('student.results.show', $completedResult);
        }

        if ($test->is_general && $test->questions->contains(
            fn (Question $question): bool => ($question->question_type ?? Test::QUESTION_TYPE_MULTIPLE_CHOICE) !== Test::QUESTION_TYPE_MULTIPLE_CHOICE
                || count($question->options ?? []) !== 4,
        )) {
            abort(409, 'This general test needs to be refreshed before it can be submitted.');
        }

        $retakeGrant = $test->is_scheduled_exam ? $this->startActiveExamRetake($test) : null;
        $submittedAnswers = $request->input('answers', []);
        $timeLimitSeconds = $this->timeLimitSeconds($test);
        $startedAtTimestamp = null;
        $timeTakenSeconds = null;
        $isTimedOut = false;
        if ($timeLimitSeconds !== null) {
            $startedAt = $retakeGrant?->started_at?->timestamp
                ?? session()->get($this->examTimerSessionKey($test));
            if ($startedAt === null) {
                $startedAt = now()->timestamp;
                session()->put($this->examTimerSessionKey($test), $startedAt);
            }

            $startedAtTimestamp = (int) $startedAt;
            $elapsedSeconds = max(0, now()->timestamp - $startedAtTimestamp);
            $isTimedOut = $elapsedSeconds >= $timeLimitSeconds;
            $timeTakenSeconds = min($elapsedSeconds, $timeLimitSeconds);
            if ($isTimedOut) {
                $submittedAnswers = [];
            }
        }
        $questionOrder = $request->input('question_order', []); // 1. Catch the shuffled order
        $score = 0;
        $quizSnapshot = [];

        // 2. Group all questions by their ID so we can easily look them up
        $questionsById = $test->questions->keyBy('id');

        // 3. Rebuild the questions array in the EXACT order they were displayed on screen
        $orderedQuestions = [];
        if (! empty($questionOrder)) {
            foreach ($questionOrder as $qId) {
                if (isset($questionsById[$qId])) {
                    $orderedQuestions[] = $questionsById[$qId];
                }
            }
        } else {
            $orderedQuestions = $test->questions; // Fallback just in case
        }

        // 4. Use the sorted array to build the snapshot!
        foreach ($orderedQuestions as $question) {
            $userAns = isset($submittedAnswers[$question->id]) ? trim($submittedAnswers[$question->id]) : null;
            $rawCorrect = trim($question->correct_answer);
            $questionType = $question->question_type ?? $test->question_type;

            $options = is_string($question->options) ? json_decode($question->options, true) : $question->options;
            $actualCorrect = $rawCorrect;

            if ($questionType === Test::QUESTION_TYPE_IDENTIFICATION) {
                $userAns = $userAns === null ? null : mb_strtoupper($userAns);
                $actualCorrect = mb_strtoupper($rawCorrect);
            } elseif (is_array($options)) {
                foreach ($options as $optIndex => $opt) {
                    $cleanOpt = trim($opt);
                    if (strcasecmp($cleanOpt, $rawCorrect) === 0 || strtoupper($rawCorrect) === chr(65 + $optIndex)) {
                        $actualCorrect = $cleanOpt;
                        break;
                    }
                }
            }

            $isCorrect = false;
            $isCorrectAnswer = $userAns !== null
                && ($questionType === Test::QUESTION_TYPE_IDENTIFICATION
                    ? $userAns === $actualCorrect
                    : strcasecmp($userAns, $actualCorrect) === 0);
            if ($isCorrectAnswer) {
                $score++;
                $isCorrect = true;
            }

            // Build the snapshot for EACH question
            $quizSnapshot[] = [
                'question_id' => $question->id,
                'question_bank_item_id' => $question->question_bank_item_id,
                // NEW: Stronger regex to strip out numbers like "9. " before saving
                'question_text' => preg_replace('/^(Q?\d+[\.\)\-]?\s*)+/i', '', trim($question->question)),
                'topic' => $question->topic ?? ($test->is_general ? null : $test->topic),
                'question_type' => $questionType,
                'difficulty' => $question->difficulty,
                'options' => is_array($options) ? $options : [],
                'correct_answer' => $actualCorrect,
                'user_answer' => $userAns,
                'is_correct' => $isCorrect,
                'explanation' => $question->explanation,
            ];
        }

        // Save it securely bypassing any background AI events
        /** @var array{result: Result, already_submitted: bool} $submission */
        $submission = DB::transaction(function () use (
            $test,
            $score,
            $quizSnapshot,
            $startedAtTimestamp,
            $timeTakenSeconds,
            $isTimedOut,
        ): array {
            if ($test->is_scheduled_exam) {
                Test::query()->lockForUpdate()->findOrFail($test->id);

                $existingResult = $this->findCompletedScheduledExamResult($test);
                $retakeGrant = $this->activeExamRetakeGrant($test, true);
                if ($existingResult !== null && $retakeGrant === null) {
                    return ['result' => $existingResult, 'already_submitted' => true];
                }
            } else {
                $retakeGrant = null;
            }

            $result = Result::withoutEvents(function () use (
                $test,
                $score,
                $quizSnapshot,
                $startedAtTimestamp,
                $timeTakenSeconds,
                $isTimedOut,
            ): Result {
                $newResult = new Result;
                $newResult->user_id = auth()->id();
                $newResult->test_id = $test->id;
                $newResult->score = $score;
                $newResult->total_questions = count($quizSnapshot);
                $newResult->passed = count($quizSnapshot) > 0
                    && ($score / count($quizSnapshot)) * 100 >= Test::PASSING_SCORE_PERCENTAGE;
                $newResult->started_at = $startedAtTimestamp === null
                    ? null
                    : now()->setTimestamp($startedAtTimestamp);
                $newResult->time_taken_seconds = $timeTakenSeconds;
                $newResult->submission_type = $isTimedOut ? 'time_expired' : 'manual';
                $newResult->answers = $quizSnapshot;
                $newResult->save();

                return $newResult;
            });

            if ($retakeGrant !== null) {
                $retakeGrant->update(['consumed_at' => now()]);
            }

            return ['result' => $result, 'already_submitted' => false];
        });
        if ($submission['already_submitted']) {
            return redirect()->route('student.results.show', $submission['result']);
        }
        $result = $submission['result'];

        auth()->user()->recordQuizCompletionStreak();

        // --- GAMIFICATION LOGIC ---
        $user = auth()->user();
        $totalQuestions = count($quizSnapshot);

        // Points reflect the latest attempt, so a later lower score replaces the previous score.
        $previousResult = Result::where('user_id', $user->id)
            ->where('test_id', $test->id)
            ->where('id', '!=', $result->id)
            ->latest('created_at')
            ->latest('id')
            ->first();

        $oldPoints = 0;
        if ($previousResult !== null && $previousResult->total_questions > 0) {
            $oldPoints = round(($previousResult->score / $previousResult->total_questions) * 30);
        }

        $newPoints = $totalQuestions > 0 ? round(($score / $totalQuestions) * 30) : 0;
        $user->update([
            'total_points' => max(0, $user->total_points + $newPoints - $oldPoints),
        ]);
        $user->refresh();
        $achievementUnlockService->syncEligible($user);

        return redirect()->route('student.results.show', $result);
    }

    public function showResult(Result $result)
    {
        if ($result->user_id !== auth()->id() && $result->test->department_id !== auth()->user()->department_id) {
            abort(403, 'Unauthorized access.');
        }

        $result->load('test.questions', 'test.department');

        $score = $result->score ?? 0;
        $totalQuestions = $result->total_questions > 0 ? $result->total_questions : 1;

        $percentage = ($score / $totalQuestions) * 100;

        // Force calculation on the fly so legacy records with old database states adapt immediately
        $result->percentage = $percentage;
        $result->passed = $percentage >= Test::PASSING_SCORE_PERCENTAGE;

        return redirect()->back()->with('completed_result_id', $result->id);
    }

    public function quiz(Request $request, Subtopic $subtopic, AiService $aiService)
    {
        $validated = $request->validate([
            'question_type' => ['sometimes', 'in:multiple_choice,identification'],
        ]);
        $questionType = $validated['question_type'] ?? Test::QUESTION_TYPE_MULTIPLE_CHOICE;
        $user = auth()->user();

        abort_unless(
            Module::forStudent($user)->whereKey($subtopic->module_id)->exists(),
            404,
        );

        $rawText = $subtopic->content ?? $subtopic->description ?? '';

        $filePath = $subtopic->file_path ?? ($subtopic->reviewer->file_path ?? null);

        if (empty(trim($rawText)) && ! empty($filePath) && Storage::disk('public')->exists($filePath)) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            if ($extension === 'pdf') {
                $rawText = $this->extractSubtopicStudyText($subtopic);
                $subtopic->refresh();

                if ($subtopic->text_extraction_status !== Subtopic::TEXT_EXTRACTION_COMPLETED) {
                    $message = $subtopic->text_extraction_status === Subtopic::TEXT_EXTRACTION_FAILED
                        ? 'The reviewer text could not be extracted. Please ask your faculty member to check the uploaded PDF.'
                        : 'The reviewer text is still being prepared. Please try again in a moment.';

                    return back()->withErrors(['quiz' => $message]);
                }
            } elseif ($extension === 'txt') {
                $rawText = file_get_contents(Storage::disk('public')->path($filePath)) ?: '';
            }
        }

        // 3. Restrict generated quiz material to the uploaded reviewer.
        if (empty(trim($rawText))) {
            // Fallback if absolutely no text is found
            $reviewerText = 'Generate an academic quiz strictly focused on the core concepts of: '.$subtopic->title.'. Do not include outside topics.';
        } else {
            // Wrap the extracted text in a strict AI command to ignore other chapters
            $reviewerText = "CRITICAL INSTRUCTION: The following study material contains multiple different chapters or topics. You MUST ignore everything else and ONLY generate questions based on the specific topic: '{$subtopic->title}'. Do not generate questions for any other topics found in this text. \n\nSTUDY MATERIAL TEXT:\n".$rawText;
        }

        try {
            $previousQuestions = $this->previousQuestionsFor(
                false,
                $user->department_id,
                $user->course_id,
                $subtopic->id,
                $subtopic->title,
                $user->id,
            );
            $generatedQuestions = $aiService->generateSubtopicQuiz(
                $subtopic->title,
                $reviewerText,
                10,
                $questionType,
                $previousQuestions,
            );
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['quiz' => $exception->getMessage()]);
        }

        // 5. Create a new Test record
        $test = Test::create([
            'title' => $subtopic->title.' - Review Quiz',
            'topic' => $subtopic->title,
            'department_id' => $user->department_id ?? 1,
            'course_id' => $user->course_id,
            'created_by' => $user->id,
            'is_general' => false,
            'question_type' => $questionType,
            'subtopic_id' => $subtopic->id,
        ]);

        // 6. Save the generated questions
        foreach ($generatedQuestions as $qData) {
            $test->questions()->create([
                'question_type' => $questionType,
                'question' => $qData['question'],
                'topic' => $qData['topic'] ?? $subtopic->title,
                'options' => $questionType === Test::QUESTION_TYPE_MULTIPLE_CHOICE
                    ? collect($qData['options'])->shuffle()->values()->all()
                    : [],
                'correct_answer' => $questionType === Test::QUESTION_TYPE_IDENTIFICATION
                    ? mb_strtoupper($qData['correct_answer'])
                    : $qData['correct_answer'],
                'explanation' => $qData['explanation'] ?? '',
            ]);
        }

        return view('student.take-test', [
            'test' => $test,
            'subtopic' => $subtopic,
        ]);
    }

    private function ensureStudentCanAccessTest(Test $test): ?Result
    {
        $student = auth()->user();
        abort_unless($test->department_id === $student->department_id, 403, 'Unauthorized department context.');

        if ($test->is_scheduled_exam) {
            abort_unless(
                $student->course_id !== null && $test->course_id === $student->course_id,
                404,
            );
            $completedResult = $this->findCompletedScheduledExamResult($test);
            if ($completedResult !== null && $this->activeExamRetakeGrant($test) === null) {
                return $completedResult;
            }
            abort_unless($test->isAvailableAt(), 403, 'This examination is not currently available.');
        }

        return null;
    }

    private function findCompletedScheduledExamResult(Test $test): ?Result
    {
        if (! $test->is_scheduled_exam) {
            return null;
        }

        return Result::query()
            ->where('test_id', $test->id)
            ->where('user_id', auth()->id())
            ->latest('created_at')
            ->latest('id')
            ->lockForUpdate()
            ->first();
    }

    private function activeExamRetakeGrant(Test $test, bool $lockForUpdate = false): ?TestRetakeGrant
    {
        $query = TestRetakeGrant::query()
            ->where('test_id', $test->id)
            ->where('student_id', auth()->id())
            ->whereNull('consumed_at');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function startActiveExamRetake(Test $test): ?TestRetakeGrant
    {
        return DB::transaction(function () use ($test): ?TestRetakeGrant {
            Test::query()->lockForUpdate()->findOrFail($test->id);

            $grant = $this->activeExamRetakeGrant($test, true);
            if ($grant === null) {
                return null;
            }

            if ($grant->started_at === null) {
                $grant->update(['started_at' => now()]);
            }

            return $grant->fresh();
        });
    }

    private function timeLimitSeconds(Test $test): ?int
    {
        $timeLimitMinutes = $test->time_limit ?? $test->duration_minutes;

        return $test->is_scheduled_exam && $timeLimitMinutes !== null
            ? (int) $timeLimitMinutes * 60
            : null;
    }

    private function examTimerSessionKey(Test $test): string
    {
        return 'student_exam_started_at.'.$test->id;
    }
}
