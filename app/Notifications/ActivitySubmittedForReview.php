<?php

namespace App\Notifications;

use App\Models\DonationSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ActivitySubmittedForReview extends Notification
{
    use Queueable;

    public function __construct(public DonationSchedule $activity, public ?string $submittedBy = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Activity pending QAO review',
            'activity_id' => $this->activity->id,
            'activity_title' => $this->activity->title,
            'activity_type' => $this->activity->event_type,
            'event_date' => $this->activity->event_date?->toDateString(),
            'facility_id' => $this->activity->facility_id,
            'facility_name' => $this->activity->facility?->name,
            'venue' => $this->activity->venue,
            'submitted_by' => $this->submittedBy,
            'approval_status' => 'pending',
        ];
    }
}
