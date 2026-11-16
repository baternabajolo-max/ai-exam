<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DailyFacultyQuestionBankSummaryNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, array{faculty_name: string, questions_added: int}>  $facultyCounts
     */
    public function __construct(
        public readonly string $reportDate,
        public readonly int $totalQuestionsAdded,
        public readonly array $facultyCounts,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Daily Question Bank Summary',
            'report_date' => $this->reportDate,
            'total_questions_added' => $this->totalQuestionsAdded,
            'faculty_counts' => $this->facultyCounts,
        ];
    }
}
