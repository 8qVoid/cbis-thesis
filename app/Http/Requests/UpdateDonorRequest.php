<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateDonorRequest extends StoreDonorRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['email'] = ['nullable', 'email', 'max:255', Rule::unique('donors', 'email')->ignore($this->route('donor'))];

        return $rules;
    }
}
