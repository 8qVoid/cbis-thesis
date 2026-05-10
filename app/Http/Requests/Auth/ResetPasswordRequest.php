<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

class ResetPasswordRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_type' => ['required', 'in:staff,donor'],
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'max:255'],
        ];
    }
}
