<?php

namespace App\Http\Requests;

use App\Support\PhilippinePhone;
use Illuminate\Validation\Rule;

class UpdateDonorPortalRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth('donor')->check();
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
        $donorId = auth('donor')->id();

        return [
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'sex' => ['required', 'in:male,female'],
            'blood_type' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'contact_number' => ['nullable', 'regex:/^\+639\d{9}$/', Rule::unique('donors', 'contact_number')->ignore($donorId)],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
