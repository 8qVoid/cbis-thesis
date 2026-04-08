<?php

namespace App\Listeners;

use App\Events\DonationRecorded;
use App\Models\BloodInventory;

class IncreaseInventoryFromDonation
{
    public function handle(DonationRecorded $event): void
    {
        $record = $event->record;

        BloodInventory::create([
            'facility_id' => $record->facility_id,
            'donation_record_id' => $record->id,
            'blood_type' => $record->blood_type,
            'units_available' => max(1, (int) floor($record->volume_ml / 450)),
            'expiration_date' => $record->expiration_date,
            'status' => now()->toDateString() > $record->expiration_date->toDateString() ? 'expired' : 'active',
        ]);
    }
}
