<?php

namespace App\Rules;

use App\Support\NegrosOccidentalAddress;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NegrosOccidentalAddressRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = NegrosOccidentalAddress::split(is_string($value) ? $value : null);

        if ($parts['city'] === null || $parts['barangay'] === null) {
            $fail('Please select a valid barangay and city/municipality in Negros Occidental.');
        }
    }
}
