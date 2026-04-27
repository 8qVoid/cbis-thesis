<?php

namespace App\Http\Requests;


class StoreBloodlettingRecordRequest extends BaseFormRequest
{
    public function authorize(): bool { return $this->facilityOperatorCan('manage bloodletting records'); }

    public function rules(): array
    {
        return [
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'donation_record_id' => ['required', 'integer', 'exists:donation_records,id'],
            'bloodletting_at' => ['required', 'date'],
            'verification_status' => ['required', 'in:pending,verified,rejected'],
            'findings' => ['nullable', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
