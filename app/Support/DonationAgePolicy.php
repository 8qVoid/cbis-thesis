<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class DonationAgePolicy
{
    public const MINIMUM_AGE = 18;

    public static function cutoffDate(): string
    {
        return today()->subYears(self::MINIMUM_AGE)->toDateString();
    }

    public static function isOldEnough(null|string|CarbonInterface $birthDate): bool
    {
        if ($birthDate === null || $birthDate === '') {
            return false;
        }

        try {
            return Carbon::parse($birthDate)->lte(today()->subYears(self::MINIMUM_AGE));
        } catch (\Throwable) {
            return false;
        }
    }

    public static function message(): string
    {
        return 'Donor must be at least '.self::MINIMUM_AGE.' years old to donate blood.';
    }
}
