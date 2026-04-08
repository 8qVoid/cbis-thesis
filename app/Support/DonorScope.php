<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DonorScope
{
    public static function apply(Builder $query, User $user): Builder
    {
        if ($user->isCentralAdmin()) {
            return $query;
        }

        $facilityId = $user->facility_id;

        return $query->where(function (Builder $builder) use ($facilityId): void {
            $builder->where('facility_id', $facilityId)
                ->orWhereHas('donationRecords', fn (Builder $q) => $q->where('facility_id', $facilityId))
                ->orWhereHas('eventRegistrations', fn (Builder $q) => $q->where('facility_id', $facilityId));
        });
    }
}
