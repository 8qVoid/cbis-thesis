<?php

namespace App\Notifications;

use App\Models\DonationSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventPostedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DonationSchedule $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('CBIS Event Alert: New Event Posted')
            ->greeting('Good day, '.$notifiable->first_name.'!')
            ->line('A new public blood donation or bloodletting event has been posted.')
            ->line('Event: '.$this->event->title)
            ->line('Type: '.$this->event->event_type_label)
            ->line('Facility: '.($this->event->facility?->name ?? 'N/A'))
            ->line('Date: '.($this->event->event_date?->toDateString() ?? 'N/A'))
            ->line('Time: '.$this->event->start_time.' - '.$this->event->end_time)
            ->line('Venue: '.$this->event->venue)
            ->line('Contact: '.($this->event->contact_person ?? 'N/A').' / '.($this->event->contact_number ?? 'N/A'))
            ->action('View Event', route('public.map', [
                'event_type' => $this->event->event_type,
                'event_date' => $this->event->event_date?->toDateString(),
            ]))
            ->line('Log in to your donor portal to register for this event.');
    }
}
