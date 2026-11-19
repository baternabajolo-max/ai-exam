<?php

namespace App\Notifications;

use App\Models\QuestionBankItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class QuestionBankItemDisabledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly QuestionBankItem $questionBankItem,
        public readonly User $disabledBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $questionPreview = Str::limit($this->questionBankItem->question, 120);

        return [
            'title' => 'Question disabled',
            'message' => 'Your question "'.$questionPreview.'" was disabled by '.$this->disabledBy->name.' and cannot be selected for new exams.',
            'question_id' => $this->questionBankItem->id,
            'course_id' => $this->questionBankItem->course_id,
            'topic_id' => $this->questionBankItem->topic_id,
            'url' => route('admin.question-bank.topics.show', $this->questionBankItem->topic_id),
        ];
    }
}
