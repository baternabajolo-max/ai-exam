<?php

namespace App\Notifications;

use App\Models\Test;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FacultyExamApprovalRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Test $exam,
        public readonly User $faculty,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Examination approval requested',
            'message' => $this->faculty->name.' submitted '.$this->exam->title.' for approval.',
            'exam_id' => $this->exam->id,
            'url' => route('super-admin.tests.index'),
        ];
    }
}
