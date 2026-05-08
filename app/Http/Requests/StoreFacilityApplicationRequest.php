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
            $contactNumbers = collect(explode(',', (string) $this->input('contact_number')))
                ->map(fn (string $number) => trim($number))
                ->filter()
                ->implode(', ');

            $this->merge(['contact_number' => $contactNumbers]);
        }
    }

    public function rules(): array
    {
        return [
            'organization_name' => ['required', 'string', 'max:255'],
            'facility_type' => ['required', 'in:blood_bank,hospital'],
            'contact_person' => ['required', 'string', 'max:80', 'regex:/^[\pL\s.\'-]+$/u'],
            'contact_number' => [
                'required',
                'string',
                'max:60',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (trim((string) $value) === '') {
                        $fail('Enter at least one contact number.');

                        return;
                    }

                    if (! PhilippinePhone::isValidContactNumber((string) $value, true, 2)) {
                        $fail('Enter up to two valid Philippine mobile or landline numbers separated by commas.');
                    }
                },
            ],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'doh_accreditation_number' => ['nullable', 'string', 'max:255'],
            'legitimacy_proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'doh_accreditation_proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
