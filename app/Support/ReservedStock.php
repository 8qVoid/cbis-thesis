<?php

namespace App\Support;

use App\Models\BloodReservation;

class ReservedStock
{
    public static function outstanding(int $facilityId, string $bloodType, string $component, ?int $except = null): int
    {
        return (int) BloodReservation::where('facility_id', $facilityId)
            ->where('blood_type', $bloodType)->where('component', $component)->where('status', 'approved')
            ->when($except, fn ($query) => $query->whereKeyNot($except))
            ->withSum('releases', 'units_released')->get()
            ->sum(fn ($reservation) => max(0, $reservation->units_requested - ($reservation->releases_sum_units_released ?? 0)));
    }
}
