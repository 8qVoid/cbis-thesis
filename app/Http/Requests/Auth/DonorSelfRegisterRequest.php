<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;
use App\Rules\NegrosOccidentalAddressRule;
use App\Support\PhilippinePhone;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class DonorSelfRegisterRequest extends BaseFormRequest
{
    public const EMAIL_ALREADY_REGISTERED = 'An account already uses this email. Sign in to finish email verification, or reset your password.';

    public const PHONE_ALREADY_REGISTERED = 'An account already uses this mobile number. Sign in to finish email verification, or reset your password.';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->filled('contact_number')) {
            $normalized = PhilippinePhone::normalizeMobileInput((string) $this->input('contact_number'));
            $this->merge(['contact_number' => $normalized ?? trim((string) $this->input('contact_number'))]);
        }
    }

    public function rules(): array
    {
        return [
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['required', 'distinct', Rule::in(['donor', 'patient'])],
            'event_id' => ['nullable', 'integer', 'exists:donation_schedules,id'],
            'first_name' => ['required', 'string', 'max:80', 'regex:/^[\pL\s.\'-]+$/u'],
            'last_name' => ['required', 'string', 'max:80', 'regex:/^[\pL\s.\'-]+$/u'],
            'middle_name' => ['nullable', 'string', 'max:80', 'regex:/^[\pL\s.\'-]+$/u'],
            'birth_date' => ['required', 'date', 'before:today'],
            'sex' => ['required', 'in:male,female'],
            'blood_type' => [Rule::requiredIf(fn () => in_array('donor', (array) $this->input('services', []), true)), 'nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'contact_number' => ['required', 'regex:/^\+639\d{9}$/', 'unique:users,phone'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'address' => ['required', 'string', 'max:500', new NegrosOccidentalAddressRule],
            'identity_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(10)->mixedCase()->letters()->numbers()->symbols()],
            'password_confirmation' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'services.required' => 'Select at least one service to continue.',
            'services.min' => 'Select at least one service to continue.',
            'birth_date.before' => 'Birth date must be before today.',
            'blood_type.required' => 'Select your blood type to register as a donor.',
            'email.unique' => self::EMAIL_ALREADY_REGISTERED,
            'contact_number.unique' => self::PHONE_ALREADY_REGISTERED,
        ];
    }
}
