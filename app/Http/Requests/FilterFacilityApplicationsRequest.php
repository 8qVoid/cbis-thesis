<?php

namespace App\Http\Requests;

class FilterFacilityApplicationsRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCentralAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
