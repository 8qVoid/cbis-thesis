<?php

namespace App\Notifications;

use App\Models\DonorScreening;
use Illuminate\Notifications\Notification;

class DonorScreeningUpdated extends Notification
{
    public function __construct(public DonorScreening $screening) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => 'Donation screening updated', 'status' => $this->screening->status,
            'donor_message' => $this->screening->donor_message, 'review_on' => $this->screening->review_on?->toDateString()];
    }
}
