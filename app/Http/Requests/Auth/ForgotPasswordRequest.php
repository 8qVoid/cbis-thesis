<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

class ForgotPasswordRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_type' => ['required', 'in:staff,donor'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
