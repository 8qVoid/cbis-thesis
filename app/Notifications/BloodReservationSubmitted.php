<?php

namespace App\Notifications;

use App\Models\BloodReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BloodReservationSubmitted extends Notification
{
    use Queueable;
    public function __construct(public BloodReservation $reservation) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New blood reservation', 'reservation_id' => $this->reservation->id,
            'reference' => $this->reservation->reference, 'facility_id' => $this->reservation->facility_id,
            'blood_type' => $this->reservation->blood_type, 'component' => $this->reservation->component,
        ];
    }
}
