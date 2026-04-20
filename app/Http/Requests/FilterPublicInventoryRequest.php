<?php

namespace App\Http\Requests;

use App\Models\BloodInventory;

class FilterPublicInventoryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'blood_type' => ['nullable', 'in:'.implode(',', BloodInventory::BLOOD_TYPES)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
