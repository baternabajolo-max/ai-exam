<?php

namespace App\Notifications;

use App\Models\Subtopic;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubtopicNeedsRevisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Subtopic $subtopic,
        public readonly User $reviewedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $module = $this->subtopic->module;

        return [
            'title' => 'Module topic needs revision',
            'message' => 'Your topic "'.$this->subtopic->title.'" in '.$module->title.' was flagged for revision by '.$this->reviewedBy->name.'.',
            'subtopic_id' => $this->subtopic->id,
            'module_id' => $module->id,
            'course_id' => $module->course_id,
            'url' => route('admin.modules.show', $module),
        ];
    }
}
