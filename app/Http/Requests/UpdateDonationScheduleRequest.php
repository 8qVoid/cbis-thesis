<?php

namespace App\Http\Requests;

class UpdateDonationScheduleRequest extends StoreDonationScheduleRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $schedule = $this->route('donation_schedule');
        if ($schedule && in_array($schedule->status, ['completed', 'cancelled'], true)) {
            $rules['status'] = ['required', \Illuminate\Validation\Rule::in([$schedule->status])];
        }

        $rules['photo'] = [
            $schedule?->photo_path ? 'nullable' : 'required',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:4096',
        ];

        return $rules;
    }
}
