<?php

namespace App\Http\Requests;

use App\Support\PhilippinePhone;

class StoreFacilityApplicationRequest extends BaseFormRequest
{
    private const CONTACT_NUMBER_SEGMENT_PATTERN = '/^(?:09\d{9}|9\d{9}|639\d{9}|0\d{9,10}|63\d{9,10})$/';

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
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_number' => [
                'required',
                'string',
                'max:120',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $numbers = collect(explode(',', (string) $value))
                        ->map(fn (string $number) => trim($number))
                        ->filter();

                    if ($numbers->isEmpty()) {
                        $fail('Enter at least one contact number.');
                        return;
                    }

                    foreach ($numbers as $number) {
                        $normalized = PhilippinePhone::normalizeMobile($number);
                        $digits = preg_replace('/\D+/', '', $number) ?? '';

                        if ($normalized !== null) {
                            continue;
                        }

                        if (preg_match(self::CONTACT_NUMBER_SEGMENT_PATTERN, $digits) !== 1) {
                            $fail('Enter valid Philippine mobile or landline numbers. You may separate multiple numbers with commas.');
                            return;
                        }
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
