<?php

namespace App\Http\Requests;


class StoreDonationRecordRequest extends BaseFormRequest
{
    public function authorize(): bool { return $this->facilityOperatorCan('manage donation records'); }

    public function rules(): array
    {
        return [
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'donor_id' => ['required', 'integer', 'exists:donors,id'],
            'donation_no' => ['required', 'string', 'max:50', 'unique:donation_records,donation_no'],
            'donated_at' => ['required', 'date'],
            'blood_type' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'volume_ml' => ['required', 'integer', 'min:1', 'max:5000'],
            'expiration_date' => ['required', 'date', 'after:donated_at'],
            'status' => ['required', 'in:pending,verified,rejected'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
