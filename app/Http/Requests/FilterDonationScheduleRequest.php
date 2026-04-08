<?php

namespace App\Http\Requests;

class FilterDonationScheduleRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['nullable', 'in:blood_donation,bloodletting'],
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'event_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:planned,ongoing,completed,cancelled'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
