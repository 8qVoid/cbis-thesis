<?php

namespace App\Notifications;

use App\Models\BloodInventory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BloodInventory $inventory)
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
            'title' => 'Low blood stock detected',
            'facility_id' => $this->inventory->facility_id,
            'facility_name' => $this->inventory->facility?->name,
            'blood_type' => $this->inventory->blood_type,
            'units_available' => $this->inventory->units_available,
            'expiration_date' => $this->inventory->expiration_date?->toDateString(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('CBIS Alert: Low Blood Stock')
            ->greeting('Attention,')
            ->line('A low stock inventory item was detected.')
            ->line('Facility: '.($this->inventory->facility?->name ?? 'N/A'))
            ->line('Blood Type: '.$this->inventory->blood_type)
            ->line('Units Available: '.$this->inventory->units_available)
            ->line('Expiration Date: '.($this->inventory->expiration_date?->toDateString() ?? 'N/A'))
            ->line('Please review and replenish stock as needed.');
    }
}
