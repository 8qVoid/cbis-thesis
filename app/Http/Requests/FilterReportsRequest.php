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
            'export_selection' => ['nullable', 'in:1'],
            'records' => ['required_if:export_selection,1', 'array', 'min:1'],
            'records.*' => ['required', 'distinct', 'in:inventory,donations,releases,reservations'],
            'detail' => ['nullable', 'in:details,summary,both'],
            'period' => ['nullable', 'in:month,day,range'],
            'month' => ['nullable', 'date_format:Y-m'],
            'day' => ['nullable', 'date'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
