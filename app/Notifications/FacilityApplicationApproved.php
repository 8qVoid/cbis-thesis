<?php

namespace App\Notifications;

use App\Models\Facility;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FacilityApplicationApproved extends Notification
{
    use Queueable;

    public function __construct(
        public Facility $facility,
        public string $recipientName,
        public string $recipientEmail,
        public ?string $temporaryPassword = null,        public ?string $reviewNotes = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('CBIS Facility Application Approved')
            ->greeting('Good day, '.$this->recipientName.'!')
            ->line('Your facility application has been approved by the Philippine Red Cross central administrator.')
            ->line('Facility: '.$this->facility->name)
            ->line('Registered Email: '.$this->recipientEmail)
            ->line('You may now log in to the CBIS staff portal.');

        if (! empty($this->temporaryPassword)) {
            $mail->line('Temporary Password: '.$this->temporaryPassword)
                ->line('Please change your password immediately after your first login.');
        } else {
            $mail->line('A staff account for this email already exists. Use your existing password to sign in.');
        }

        if (! empty($this->reviewNotes)) {
            $mail->line('Review Notes: '.$this->reviewNotes);
        }

        return $mail
            ->action('Open CBIS Login', url('/login'))
            ->line('Thank you for joining the centralized blood inventory platform.');
    }
}
