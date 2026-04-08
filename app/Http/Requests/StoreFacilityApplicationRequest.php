<?php

namespace App\Http\Requests;

use App\Support\PhilippinePhone;

class StoreFacilityApplicationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'organization_name' => ['required', 'string', 'max:255'],
            'facility_type' => ['required', 'in:blood_bank,hospital'],
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'regex:/^\+639\d{9}$/'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'doh_accreditation_number' => ['nullable', 'string', 'max:255'],
            'legitimacy_proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'doh_accreditation_proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
