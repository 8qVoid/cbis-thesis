<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateDonationRecordRequest extends StoreDonationRecordRequest
{
    public function rules(): array
    {
        return [
            'facility_id' => [$this->user()?->isCentralAdmin() ? 'required' : 'nullable', 'integer', 'exists:facilities,id'],
            'donor_id' => ['required', 'exists:donors,id'],
            'donation_no' => ['required', 'string', 'max:50', Rule::unique('donation_records', 'donation_no')->ignore($this->route('donation_record'))],
            'donated_at' => ['required', 'date'],
            'blood_type' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'volume_ml' => ['required', 'integer', 'min:1'],
            'expiration_date' => ['required', 'date', 'after:donated_at'],
            'status' => ['required', 'in:pending,verified,rejected'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
