<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class FacilityScope
{
    public static function apply(Builder $query, User $user): Builder
    {
        if (in_array($query->getModel()->getTable(), ['blood_inventory', 'blood_releases', 'donation_records', 'bloodletting_records', 'blood_reservations'], true)) {
            $query->whereIn('facility_id', MainChapter::ids());
        }
        if ($user->isCentralAdmin()) {
            return $query;
        }

        return $query->where('facility_id', $user->facility_id);
    }
}
