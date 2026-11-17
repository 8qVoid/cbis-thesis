<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;
use Illuminate\Support\Carbon;

class StoreBloodInventoryRequest extends BaseFormRequest
{
    public function authorize(): bool { return $this->facilityOperatorCan('manage inventory'); }

    public function messages(): array
    {
        return [
            'donation_record_id.prohibited' => 'Donation stock is added automatically. Use the existing donation inventory record.',
            'donation_record_id.unique' => 'This donation already has an inventory record. Edit the existing record instead.',
        ];
    }

    public function rules(): array
    {
        return [
            'facility_id' => [$this->user()?->isCentralAdmin() ? 'required_without:donation_record_id' : 'nullable', 'integer', 'exists:facilities,id'],
            'donation_record_id' => [$this->isMethod('post') ? 'prohibited' : 'nullable', 'integer', 'exists:donation_records,id', \Illuminate\Validation\Rule::unique('blood_inventory', 'donation_record_id')->ignore($this->route('blood_inventory')?->id)],
            'blood_type' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'component' => ['required', 'in:whole_blood,packed_red_blood_cells,platelet_concentrate,fresh_frozen_plasma'],
            'units_available' => ['required', 'integer', 'min:0', 'max:100000'],
            'expiration_date' => ['required', 'date'],
            'expiration_time' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'in:active,low_stock,expired'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('expiration_date') || $validator->errors()->has('expiration_time') || ! $this->input('expiration_date')) {
                return;
            }
            $expiresAt = Carbon::parse($this->input('expiration_date').' '.($this->input('expiration_time') ?: '23:59:59'), 'Asia/Manila');
            if ($this->input('status') === 'expired' && $expiresAt->isFuture()) {
                $validator->errors()->add('expiration_date', 'Expired stock must have a past expiration date and time.');
            } elseif ($this->input('status') !== 'expired' && $expiresAt->isPast()) {
                $validator->errors()->add('expiration_date', 'Available stock must have a future expiration date and time.');
            }
        });
    }
}
