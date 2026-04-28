<?php

namespace App\Notifications;

use App\Models\FacilityApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FacilityApplicationSubmitted extends Notification
{
    use Queueable;

    public function __construct(public FacilityApplication $application)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New facility application submitted',
            'application_id' => $this->application->id,
            'organization_name' => $this->application->organization_name,
            'facility_type' => $this->application->facility_type,
            'contact_person' => $this->application->contact_person,
            'email' => $this->application->email,
            'status' => $this->application->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('CBIS Alert: New Facility Application')
            ->greeting('Attention,')
            ->line('A new facility application has been submitted for review.')
            ->line('Organization: '.$this->application->organization_name)
            ->line('Facility Type: '.$this->application->facility_type)
            ->line('Contact Person: '.$this->application->contact_person)
            ->line('Email: '.$this->application->email)
            ->action('Review Application', route('facility-applications.show', $this->application))
            ->line('Please review the legitimacy and DOH accreditation documents.');
    }
}
