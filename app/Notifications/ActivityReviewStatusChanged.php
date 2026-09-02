<?php

namespace App\Notifications;

use App\Models\DonationSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ActivityReviewStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public DonationSchedule $activity) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Activity '.$this->activity->approval_status,
            'activity_id' => $this->activity->id,
            'activity_title' => $this->activity->title,
            'facility_id' => $this->activity->facility_id,
            'approval_status' => $this->activity->approval_status,
            'review_notes' => $this->activity->review_notes,
        ];
    }
}
