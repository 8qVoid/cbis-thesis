<?php

namespace App\Support;

final class LoginIdentity
{
    public static function sanitize(mixed $value): mixed
    {
        return is_string($value) ? trim(strip_tags($value)) : $value;
    }

    /**
     * Resolve an already sanitized login without changing database matching.
     *
     * @return array{field: string, value: string}|null
     */
    public static function credential(mixed $login): ?array
    {
        if (! is_string($login) || strlen($login) > 255) {
            return null;
        }

        if (filter_var($login, FILTER_VALIDATE_EMAIL) !== false) {
            // Preserve database email matching; case folding is only for the limiter.
            return ['field' => 'email', 'value' => $login];
        }

        $mobile = PhilippinePhone::normalizeMobileInput($login);

        return $mobile === null ? null : ['field' => 'phone', 'value' => $mobile];
    }

    public static function throttleKey(mixed $value): string
    {
        $credential = self::credential(self::sanitize($value));

        if ($credential === null) {
            return 'invalid';
        }

        return $credential['field'].':'.hash('sha256', strtolower($credential['value']));
    }
}
