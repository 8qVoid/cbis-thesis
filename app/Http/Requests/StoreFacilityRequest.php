<?php

namespace App\Http\Requests;

use App\Support\PhilippinePhone;

class StoreFacilityRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage facilities') ?? false;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->filled('contact_number')) {
            $normalized = PhilippinePhone::normalizeMobile((string) $this->input('contact_number'));
            $this->merge(['contact_number' => $normalized ?? trim((string) $this->input('contact_number'))]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'unique:facilities,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'contact_person' => ['nullable', 'string', 'max:80', 'regex:/^[\pL\s.\'-]+$/u'],
            'contact_number' => [
                'nullable',
                'string',
                'max:30',
                fn (string $attribute, mixed $value, \Closure $fail) => PhilippinePhone::isValidContactNumber((string) $value)
                    ?: $fail('Enter a valid Philippine mobile or landline number.'),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
