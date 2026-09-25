<?php

namespace App\Http\Requests;


class StoreBloodInventoryRequest extends BaseFormRequest
{
    public function authorize(): bool { return $this->facilityOperatorCan('manage inventory'); }

    public function messages(): array
    {
        return [
            'expiration_date.after_or_equal' => 'Available stock must expire today or later. For a past expiration date, select Expired.',
            'expiration_date.before' => 'Expired stock must have an expiration date before today.',
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
            'expiration_date' => ['required', 'date', $this->input('status') === 'expired' ? 'before:today' : 'after_or_equal:today'],
            'status' => ['required', 'in:active,low_stock,expired'],
        ];
    }
}
