<?php

namespace App\Support;

class DonationVolumePolicy
{
    public const MAXIMUM_ML = 5000;

    public static function rules(): array
    {
        return ['required', 'integer', 'min:1', 'max:'.self::MAXIMUM_ML];
    }
}
