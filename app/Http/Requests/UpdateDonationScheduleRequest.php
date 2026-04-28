<?php

namespace App\Http\Requests;

class UpdateDonationScheduleRequest extends StoreDonationScheduleRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $schedule = $this->route('donation_schedule');

        $rules['photo'] = [
            $schedule?->photo_path ? 'nullable' : 'required',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:4096',
        ];

        return $rules;
    }
}
