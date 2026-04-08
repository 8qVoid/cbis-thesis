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
        $inventory->units_available = $remaining;
        $inventory->status = $remaining === 0 ? 'low_stock' : ($remaining <= 5 ? 'low_stock' : 'active');

        if ($inventory->expiration_date->isPast()) {
            $inventory->status = 'expired';
        }

        $inventory->save();
    }
}
