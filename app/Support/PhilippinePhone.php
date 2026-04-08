<?php

namespace App\Support;

class PhilippinePhone
{
    public static function normalizeMobile(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', trim($value)) ?? '';

        if ($digits === '') {
            return null;
        }

        // 09XXXXXXXXX
        if (preg_match('/^09\d{9}$/', $digits) === 1) {
            return '+63'.substr($digits, 1);
        }

        // 9XXXXXXXXX
        if (preg_match('/^9\d{9}$/', $digits) === 1) {
            return '+63'.$digits;
        }

        // 639XXXXXXXXX
        if (preg_match('/^639\d{9}$/', $digits) === 1) {
            return '+'.$digits;
        }

        return null;
    }
}
