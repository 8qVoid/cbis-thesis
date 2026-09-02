<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;
use App\Support\PhilippinePhone;
use Illuminate\Validation\Rule;

class DonorSelfRegisterRequest extends BaseFormRequest
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
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['required', 'distinct', Rule::in(['donor', 'patient'])],
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'event_id' => ['nullable', 'integer', 'exists:donation_schedules,id'],
            'first_name' => ['required', 'string', 'max:80', 'regex:/^[\pL\s.\'-]+$/u'],
            'last_name' => ['required', 'string', 'max:80', 'regex:/^[\pL\s.\'-]+$/u'],
            'middle_name' => ['nullable', 'string', 'max:80', 'regex:/^[\pL\s.\'-]+$/u'],
            'birth_date' => ['required', 'date', 'before:today'],
            'sex' => ['required', 'in:male,female'],
            'blood_type' => [Rule::requiredIf(fn () => in_array('donor', (array) $this->input('services', []), true)), 'nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'contact_number' => ['required', 'regex:/^\+639\d{9}$/', 'unique:users,phone'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'address' => ['required', 'string', 'max:500'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'max:255'],
        ];
    }
}
