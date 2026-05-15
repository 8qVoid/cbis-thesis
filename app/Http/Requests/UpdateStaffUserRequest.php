<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\PhilippinePhone;
use Illuminate\Validation\Rule;

class UpdateStaffUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $currentUser = $this->user();
        $staffUser = $this->route('staffUser');

        if (! $currentUser || ! $staffUser instanceof User) {
            return false;
        }

        if ($currentUser->isCentralAdmin()) {
            return false;
        }

        return $this->facilityOperatorCan('manage users')
            && $currentUser->facility_id !== null
            && $staffUser->facility_id === $currentUser->facility_id;
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
        $staffUser = $this->route('staffUser');
        $staffUserId = $staffUser instanceof User ? $staffUser->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'nullable',
                'regex:/^\+639\d{9}$/',
                Rule::unique('users', 'phone')->ignore($staffUserId),
            ],
        ];
    }
}
