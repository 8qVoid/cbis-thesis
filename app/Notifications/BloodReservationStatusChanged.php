<?php

namespace App\Notifications;

use App\Models\BloodReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BloodReservationStatusChanged extends Notification
{
    use Queueable;
    public function __construct(public BloodReservation $reservation) {}
    public function via(object $notifiable): array { return ['database', 'mail']; }
    public function toArray(object $notifiable): array
    {
        return ['title' => 'Reservation status updated', 'reservation_id' => $this->reservation->id,
            'reference' => $this->reservation->reference, 'status' => $this->reservation->status,
            'review_notes' => $this->reservation->review_notes];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('CBIS: Blood reservation '.str($this->reservation->status)->headline())
            ->greeting('Good day, '.($notifiable->first_name ?: $notifiable->name).'!')
            ->line('Your blood reservation '.$this->reservation->reference.' is now '.str($this->reservation->status)->headline().'.')
            ->when($this->reservation->review_notes, fn (MailMessage $mail) => $mail->line('Staff note: '.$this->reservation->review_notes))
            ->action('View Blood Request', route('reservations.show', $this->reservation));
    }
}
