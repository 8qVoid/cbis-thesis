<?php

namespace App\Http\Requests;


class StoreBloodReleaseRequest extends BaseFormRequest
{
    public function authorize(): bool { return $this->facilityOperatorCan('manage blood releases'); }

    public function rules(): array
    {
        return [
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'blood_inventory_id' => ['required', 'integer', 'exists:blood_inventory,id'],
            'patient_name' => ['nullable', 'string', 'max:255'],
            'requesting_unit' => ['nullable', 'string', 'max:255'],
            'released_at' => ['required', 'date'],
            'units_released' => ['required', 'integer', 'min:1', 'max:100000'],
            'purpose' => ['nullable', 'string', 'max:500'],
        ];
    }
}
