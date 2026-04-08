<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class FacilityScope
{
    public static function apply(Builder $query, User $user): Builder
    {
        if ($user->isCentralAdmin()) {
            return $query;
        }

        return $query->where('facility_id', $user->facility_id);
    }
}
