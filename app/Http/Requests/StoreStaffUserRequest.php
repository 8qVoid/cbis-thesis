<?php

namespace App\Http\Requests;

use App\Support\PhilippinePhone;
use Illuminate\Validation\Rule;

class StoreStaffUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->facilityOperatorCan('manage users');
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->filled('phone')) {
            $normalized = PhilippinePhone::normalizeMobile((string) $this->input('phone'));
            $this->merge(['phone' => $normalized ?? trim((string) $this->input('phone'))]);
        }
    }

    public function rules(): array
    {
        $user = $this->user();
        $isCentralAdmin = $user?->isCentralAdmin() ?? false;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'regex:/^\+639\d{9}$/', 'unique:users,phone'],
            'facility_id' => [$isCentralAdmin ? 'required' : 'nullable', 'integer', 'exists:facilities,id'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'max:255'],
            'role' => [
                'required',
                Rule::in([
                    'Facilitator',
                    'Medical Staff / Nurse',
                ]),
            ],
        ];
    }
}
