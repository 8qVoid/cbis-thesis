<?php

namespace App\Notifications;

use App\Models\BloodReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BloodReservationStatusChanged extends Notification
{
    use Queueable;
    public function __construct(public BloodReservation $reservation) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toArray(object $notifiable): array
    {
        return ['title' => 'Reservation status updated', 'reservation_id' => $this->reservation->id,
            'reference' => $this->reservation->reference, 'status' => $this->reservation->status,
            'review_notes' => $this->reservation->review_notes];
    }
}
