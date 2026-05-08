<?php

namespace App\Http\Requests;

use App\Models\BloodInventory;
use App\Support\FacilityScope;
use Illuminate\Validation\Validator;

class StoreBloodReleaseRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->facilityOperatorCan('manage blood releases');
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'blood_inventory_id' => ['required', 'integer', 'exists:blood_inventory,id'],
            'patient_name' => ['nullable', 'string', 'max:255'],
            'requesting_unit' => ['nullable', 'string', 'max:255'],
            'released_at' => ['required', 'date'],
            'units_released' => ['required', 'integer', 'min:1', 'max:100000'],
            'purpose' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();
            $inventory = $user
                ? FacilityScope::apply(BloodInventory::query(), $user)->find($this->integer('blood_inventory_id'))
                : null;

            if (! $inventory) {
                $validator->errors()->add('blood_inventory_id', 'The selected inventory item is not available.');

                return;
            }

            if ($inventory->status === 'expired' || $inventory->expiration_date?->isPast()) {
                $validator->errors()->add('blood_inventory_id', 'Expired blood inventory cannot be released.');
            }

            if ($inventory->units_available < 1) {
                $validator->errors()->add('blood_inventory_id', 'This inventory item has no units available.');
            }

            if ($this->integer('units_released') > $inventory->units_available) {
                $validator->errors()->add(
                    'units_released',
                    'Units released cannot exceed the available inventory of '.$inventory->units_available.'.'
                );
            }
        });
    }
}
