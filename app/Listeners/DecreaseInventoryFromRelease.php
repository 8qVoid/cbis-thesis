<?php

namespace App\Listeners;

use App\Events\BloodReleased;

class DecreaseInventoryFromRelease
{
    public function handle(BloodReleased $event): void
    {
        $release = $event->release;
        $inventory = $release->inventory;

        if (! $inventory) {
            return;
        }

        $remaining = max(0, $inventory->units_available - $release->units_released);
        $threshold = match ($inventory->component) {
            'whole_blood', 'packed_red_blood_cells' => 20,
            'platelet_concentrate' => 5,
            'fresh_frozen_plasma' => 10,
            default => 5,
        };

        $inventory->units_available = $remaining;
        $inventory->status = $remaining <= $threshold ? 'low_stock' : 'active';

        if ($inventory->status === 'active') {
            $inventory->last_low_stock_alert_at = null;
        }

        if ($inventory->expiration_date->isPast()) {
            $inventory->status = 'expired';
        }

        $inventory->save();
    }
}
