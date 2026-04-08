<?php

namespace App\Http\Requests;

class FilterPublicEventsRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['nullable', 'in:blood_donation,bloodletting'],
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'event_date' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
