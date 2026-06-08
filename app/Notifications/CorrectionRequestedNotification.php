<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CorrectionRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $requesterName,
        protected string $workDate,
        protected int    $correctionId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'correction_requested',
            'title'   => 'Correction Request',
            'body'    => "{$this->requesterName} requested a correction for {$this->workDate}.",
            'url'     => '/admin/attendance/corrections',
            'meta'    => ['correction_id' => $this->correctionId],
        ];
    }
}
