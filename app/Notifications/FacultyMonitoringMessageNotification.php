<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FacultyMonitoringMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $senderName,
        public readonly string $message,
        public readonly int $courseId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Message from '.$this->senderName,
            'message' => $this->message,
            'sender' => $this->senderName,
            'url' => route('admin.question-bank.index', ['course_id' => $this->courseId]),
        ];
    }
}
