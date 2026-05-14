<?php

namespace App\Http\Requests;

class FilterReportsRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', 'in:month,day,range'],
            'month' => ['nullable', 'date_format:Y-m'],
            'day' => ['nullable', 'date'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
