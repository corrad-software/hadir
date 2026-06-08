<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CorrectionReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $status,
        protected string $workDate,
        protected string $reviewerName,
        protected int    $correctionId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $verb = $this->status === 'approved' ? 'approved' : 'rejected';

        return [
            'type'  => 'correction_reviewed',
            'title' => 'Correction ' . ucfirst($verb),
            'body'  => "Your correction request for {$this->workDate} was {$verb} by {$this->reviewerName}.",
            'url'   => '/admin/attendance/my-history',
            'meta'  => ['correction_id' => $this->correctionId, 'status' => $this->status],
        ];
    }
}
