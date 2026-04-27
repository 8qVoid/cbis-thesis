<?php

namespace App\Http\Requests;


class StoreBloodInventoryRequest extends BaseFormRequest
{
    public function authorize(): bool { return $this->facilityOperatorCan('manage inventory'); }

    public function rules(): array
    {
        return [
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'donation_record_id' => ['nullable', 'integer', 'exists:donation_records,id'],
            'blood_type' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'units_available' => ['required', 'integer', 'min:0', 'max:100000'],
            'expiration_date' => ['required', 'date'],
            'status' => ['required', 'in:active,low_stock,expired'],
        ];
    }
}
