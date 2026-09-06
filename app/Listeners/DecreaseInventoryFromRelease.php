<?php

namespace App\Listeners;

use App\Events\BloodReleased;
use App\Models\BloodInventory;
use Illuminate\Validation\ValidationException;

class DecreaseInventoryFromRelease
{
    public function handle(BloodReleased $event): void
    {
        $release = $event->release;
        $inventory = BloodInventory::whereKey($release->blood_inventory_id)->lockForUpdate()->first();

        if (! $inventory) {
            throw ValidationException::withMessages(['blood_inventory_id' => 'This inventory item is no longer available.']);
        }

        if ($inventory->status === 'expired' || $inventory->expiration_date->isBefore(today())
            || $inventory->units_available < $release->units_released) {
            throw ValidationException::withMessages(['units_released' => 'Stock changed or expired. Reload the inventory before recording this release.']);
        }

        $remaining = $inventory->units_available - $release->units_released;
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

        if ($inventory->expiration_date->isBefore(today())) {
            $inventory->status = 'expired';
        }

        $inventory->save();
    }
}
