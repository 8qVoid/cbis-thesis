<?php

namespace App\Http\Requests;

class UpdateBloodBankLocationRequest extends StoreBloodBankLocationRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $location = $this->route('blood_bank_location');

        $rules['photo'] = [
            $location?->photo_path ? 'nullable' : 'required',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:4096',
        ];

        return $rules;
    }
}
