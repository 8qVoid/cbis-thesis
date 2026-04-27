<?php

namespace App\Http\Requests;

use App\Support\PhilippinePhone;

class StoreDonationScheduleRequest extends BaseFormRequest
{
    public function authorize(): bool { return $this->facilityOperatorCan('manage schedules'); }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->filled('contact_number')) {
            $normalized = PhilippinePhone::normalizeMobile((string) $this->input('contact_number'));
            $this->merge(['contact_number' => $normalized ?? trim((string) $this->input('contact_number'))]);
        }
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'title' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'in:blood_donation,bloodletting'],
            'event_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'venue' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'regex:/^\+639\d{9}$/'],
            'is_public' => ['nullable', 'boolean'],
            'status' => ['required', 'in:planned,ongoing,completed,cancelled'],
        ];
    }
}
