<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentResultController extends Controller
{
    public function index()
    {
        $results = Result::query()
            ->whereBelongsTo(auth()->user())
            ->whereHas('test', fn ($query) => $query->where('is_general', true))
            ->with('test')
            ->latest()
            ->paginate(20);

        return view('student.results.index', compact('results'));
    }

    public function show(Result $result): View
    {
        $questionsData = $this->prepareResult($result);
        $topicPerformance = $this->topicPerformance(
            $questionsData,
            $result->test?->topic ?? $result->test?->subtopic?->title,
        );
        $studyAdvice = $this->studyAdvice($questionsData, $topicPerformance);
        $returnUrl = $result->test?->is_scheduled_exam
            ? route('student.exams.index')
            : route('student.learn.index');

        return view('student.results.show', [
            'result' => $result,
            'questionsData' => $questionsData,
            'studyAdvice' => $studyAdvice,
            'isAdminView' => false,
            'returnUrl' => $returnUrl,
            'returnLabel' => $result->test?->is_scheduled_exam ? 'Return to Examination' : 'Return to Modules',
        ]);
    }

    public function adminShow(Request $request, User $student, Result $result): View
    {
        $isFacultyViewer = $request->user()->role === 'admin';
        $isSuperAdminViewer = $request->user()->role === 'super-admin';

        abort_unless(
            (($isFacultyViewer && $request->user()->department_id === $student->department_id) || $isSuperAdminViewer)
            && $student->role === 'student'
            && $result->user_id === $student->id
            && $result->test()->where('department_id', $student->department_id)->exists(),
            404,
        );

        $questionsData = $this->resultDetails($result);
        $returnUrl = $isFacultyViewer
            ? route('admin.students.show', $student)
            : route('super-admin.students.show', array_merge(
                ['student' => $student],
                $request->only(['college_id', 'student_search']),
            ));

        return view('student.results.show', [
            'result' => $result,
            'questionsData' => $questionsData,
            'isAdminView' => true,
            'returnUrl' => $returnUrl,
            'returnLabel' => 'Return to Student Profile',
        ]);
    }

    public function print(Result $result): View
    {
        $questionsData = $this->prepareResult($result);

        return view('student.results.print', compact('result', 'questionsData'));
    }

    private function prepareResult(Result $result): array
    {
        abort_unless($result->user_id === auth()->id(), 403, 'Unauthorized access.');

        return $this->resultDetails($result);
    }

    private function resultDetails(Result $result): array
    {
        $result->loadMissing('test.questions', 'test.department', 'test.subtopic');
        $questionsData = $result->answers;
        if (! is_array($questionsData)) {
            $questionsData = is_string($questionsData) ? json_decode($questionsData, true) : [];
        }
        $questionsData = is_array($questionsData) ? $questionsData : [];

        $totalQuestions = $result->total_questions ?: count($questionsData);
        $result->total_questions = max(1, $totalQuestions);
        $result->percentage = (($result->score ?? 0) / $result->total_questions) * 100;
        $result->passed = $result->percentage >= 75;

        return $questionsData;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questionsData
     * @return array<int, array{topic: string, total: int, correct: int, incorrect: int, accuracy: int, focus_points: array<int, string>}>
     */
    private function topicPerformance(array $questionsData, ?string $defaultTopic): array
    {
        $topicStats = [];

        foreach ($questionsData as $answer) {
            if (! is_array($answer)) {
                continue;
            }

            $topic = is_scalar($answer['topic'] ?? null)
                ? trim((string) $answer['topic'])
                : '';
            if ($topic === '' || mb_strtolower($topic) === 'general') {
                $topic = trim((string) $defaultTopic);
            }
            if ($topic === '' || mb_strtolower($topic) === 'general') {
                $topic = 'Topic not specified';
            }
            $topicKey = mb_strtolower($topic);
            $topicStats[$topicKey] ??= [
                'topic' => $topic,
                'total' => 0,
                'correct' => 0,
                'incorrect' => 0,
                'focus_points' => [],
            ];
            $topicStats[$topicKey]['total']++;

            if (filter_var($answer['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $topicStats[$topicKey]['correct']++;

                continue;
            }

            $topicStats[$topicKey]['incorrect']++;
            $correctAnswer = is_scalar($answer['correct_answer'] ?? null)
                ? trim((string) $answer['correct_answer'])
                : '';
            if ($correctAnswer !== '' && mb_strtolower($correctAnswer) !== 'not answered') {
                $topicStats[$topicKey]['focus_points'][mb_strtolower($correctAnswer)] = mb_substr($correctAnswer, 0, 100);
            }
        }

        $topicPerformance = [];
        foreach ($topicStats as $topicStat) {
            $topicStat['accuracy'] = (int) round(($topicStat['correct'] / $topicStat['total']) * 100);
            $topicStat['focus_points'] = array_values(array_slice($topicStat['focus_points'], 0, 3));
            $topicPerformance[] = $topicStat;
        }

        usort($topicPerformance, static fn (array $left, array $right): int => $left['accuracy'] <=> $right['accuracy']
            ?: $right['incorrect'] <=> $left['incorrect']);

        return $topicPerformance;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questionsData
     * @param  array<int, array<string, mixed>>  $topicPerformance
     * @return array{
     *     summary: string,
     *     focus_areas: array<int, array<string, mixed>>,
     *     strength_areas: array<int, array<string, mixed>>,
     *     answer_pattern: array<string, mixed>|null,
     *     practical_steps: array<int, string>
     * }
     */
    private function studyAdvice(array $questionsData, array $topicPerformance): array
    {
        $focusAreas = array_values(array_filter(
            $topicPerformance,
            static fn (array $topic): bool => $topic['incorrect'] > 0,
        ));
        $strengthAreas = array_slice(array_values(array_filter(
            $topicPerformance,
            static fn (array $topic): bool => $topic['correct'] > 0 && $topic['accuracy'] >= 75,
        )), 0, 3);
        $incorrectCount = array_sum(array_column($focusAreas, 'incorrect'));
        $topicNames = array_map(static fn (array $topic): string => $topic['topic'], array_slice($focusAreas, 0, 3));

        if ($focusAreas === []) {
            $summary = 'Excellent work—you answered every question correctly. Keep these concepts fresh by applying them to new examples.';
            $practicalSteps = [
                'Try explaining the main ideas in your own words without looking at the reviewer.',
                'Practice applying the concepts to a new example, not only recalling definitions.',
                'Keep building on your strongest topics with a mixed set of questions.',
            ];
        } else {
            $summary = 'Use the missed answers below to guide your review, starting with '.implode(', ', $topicNames).'.';
            $firstFocus = $focusAreas[0];
            $focusPointText = $firstFocus['focus_points'] === []
                ? $firstFocus['topic']
                : implode(', ', array_slice($firstFocus['focus_points'], 0, 3));
            $practicalSteps = [
                'Revisit '.$firstFocus['topic'].' and focus on '.mb_substr($focusPointText, 0, 180).'.',
                'Before reading each correction, try the missed question again from memory; then check why the correct answer fits.',
                'Make up a fresh example involving '.$firstFocus['topic'].' and explain which concept applies.',
            ];
        }

        $answerPattern = $this->repeatedAnswerPattern($questionsData);
        if ($answerPattern === null && $incorrectCount >= 2) {
            $answerPattern = [
                'type' => 'varied_misses',
                'message' => 'Your missed answers did not repeat the same wrong choice, so focus on strengthening the concepts listed above.',
            ];
        }

        if ($answerPattern !== null && $answerPattern['type'] === 'repeated_choice') {
            $practicalSteps[2] = 'Make a quick compare-and-contrast note for '.$answerPattern['selected_answer']
                .' and '.$answerPattern['correct_answer'].', then explain when each applies.';
        }

        return [
            'summary' => $summary,
            'focus_areas' => $focusAreas,
            'strength_areas' => $strengthAreas,
            'answer_pattern' => $answerPattern,
            'practical_steps' => $practicalSteps,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $questionsData
     * @return array<string, mixed>|null
     */
    private function repeatedAnswerPattern(array $questionsData): ?array
    {
        $answerPairs = [];

        foreach ($questionsData as $answer) {
            if (! is_array($answer) || filter_var($answer['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            $selectedAnswer = is_scalar($answer['user_answer'] ?? null) ? trim((string) $answer['user_answer']) : '';
            $correctAnswer = is_scalar($answer['correct_answer'] ?? null) ? trim((string) $answer['correct_answer']) : '';
            if ($selectedAnswer === '' || $correctAnswer === '' || mb_strtolower($selectedAnswer) === mb_strtolower($correctAnswer)) {
                continue;
            }

            $pairKey = mb_strtolower($selectedAnswer)."\0".mb_strtolower($correctAnswer);
            $answerPairs[$pairKey] ??= [
                'type' => 'repeated_choice',
                'selected_answer' => mb_substr($selectedAnswer, 0, 100),
                'correct_answer' => mb_substr($correctAnswer, 0, 100),
                'count' => 0,
            ];
            $answerPairs[$pairKey]['count']++;
        }

        usort($answerPairs, static fn (array $left, array $right): int => $right['count'] <=> $left['count']);
        $mostCommonPattern = $answerPairs[0] ?? null;

        if (($mostCommonPattern['count'] ?? 0) < 2) {
            return null;
        }

        return $mostCommonPattern;
    }
}
