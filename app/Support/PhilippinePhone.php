<?php

namespace App\Support;

class PhilippinePhone
{
    /**
     * Normalize a value typed into a phone form. Public forms accept digits
     * only; symbols and letters must be rejected rather than silently removed.
     */
    public static function normalizeMobileInput(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || preg_match('/^\d+$/', $value) !== 1) {
            return null;
        }

        return self::normalizeMobile($value);
    }

    public static function normalizeMobile(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', trim($value)) ?? '';

        if ($digits === '') {
            return null;
        }

        // Fixed UI prefix 09 + 9 typed digits.
        if (preg_match('/^\d{9}$/', $digits) === 1) {
            return '+639'.$digits;
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

    public static function mobileSuffix(?string $value): string
    {
        $normalized = self::normalizeMobile($value);

        return $normalized ? substr($normalized, 4) : '';
    }

    public static function isValidContactNumber(?string $value, bool $allowMultiple = false, int $maxNumbers = 1): bool
    {
        if ($value === null || trim($value) === '') {
            return true;
        }

        $numbers = $allowMultiple ? explode(',', $value) : [$value];
        $numbers = array_values(array_filter(array_map('trim', $numbers), fn (string $number): bool => $number !== ''));

        if ($numbers === [] || count($numbers) > $maxNumbers) {
            return false;
        }

        foreach ($numbers as $number) {
            if (preg_match('/^\d+$/', $number) !== 1) {
                return false;
            }

            if (self::normalizeMobile($number) !== null) {
                continue;
            }

            if (preg_match('/^(?:0\d{8,10}|63\d{8,10})$/', $number) === 1) {
                continue;
            }

            return false;
        }

        return true;
    }
}
