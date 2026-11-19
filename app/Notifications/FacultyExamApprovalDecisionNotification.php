<?php

namespace App\Notifications;

use App\Models\Test;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FacultyExamApprovalDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Test $exam,
        public readonly string $decision,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $approved = $this->decision === Test::APPROVAL_STATUS_APPROVED;
        $decisionLabel = $approved ? 'approved' : 'rejected';
        $comment = trim((string) $this->exam->rejection_comment);
        $message = 'Your examination "'.$this->exam->title.'" was '.$decisionLabel.' by Admin.';

        if (! $approved && $comment !== '') {
            $message .= ' Admin comment: '.$comment;
        }

        return [
            'title' => 'Examination '.ucfirst($decisionLabel),
            'message' => $message,
            'exam_id' => $this->exam->id,
            'decision' => $this->decision,
            'comment' => $comment !== '' ? $comment : null,
            'url' => route('admin.tests.index'),
        ];
    }
}
