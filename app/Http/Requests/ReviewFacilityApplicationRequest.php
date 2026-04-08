<?php

namespace App\Http\Requests;

class ReviewFacilityApplicationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCentralAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:approved,rejected,pending'],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
