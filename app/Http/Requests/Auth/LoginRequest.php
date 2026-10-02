<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;
use App\Support\LoginIdentity;

class LoginRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $login = $this->input('login');
        parent::prepareForValidation();
        $this->merge(['login' => LoginIdentity::sanitize($login)]);
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'in:on,1,0,true,false'],
        ];
    }
}
