<?php

namespace App\Http\Requests;

use App\Support\PhilippinePhone;

class StoreBloodBankLocationRequest extends BaseFormRequest
{
    public function authorize(): bool { return $this->user()?->can('manage locations') ?? false; }

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
            'facility_id' => ['nullable', 'exists:facilities,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['required', 'string', 'max:255'],
            'contact_number' => ['nullable', 'regex:/^\+639\d{9}$/'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
