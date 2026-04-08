<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Meta fields allowed in request payloads but not part of the business schema.
     *
     * @var array<int, string>
     */
    protected array $metaFields = ['_token', '_method', 'page'];
    protected array $sensitiveFields = ['password', 'password_confirmation', 'current_password'];

    protected function prepareForValidation(): void
    {
        $this->replace($this->sanitizeArray($this->all()));
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $allowed = array_merge($this->allowedFields(), $this->metaFields);
            $unexpected = array_values(array_diff(array_keys($this->all()), $allowed));

            if ($unexpected !== []) {
                $validator->errors()->add(
                    'request',
                    'Unexpected field(s): '.implode(', ', $unexpected).'.'
                );
            }
        });
    }

    /**
     * @return array<int, string>
     */
    protected function allowedFields(): array
    {
        return collect(array_keys($this->rules()))
            ->map(fn (string $key): string => explode('.', $key)[0])
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function sanitizeArray(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = $this->sanitizeArray($value);
                continue;
            }

            if (is_string($value)) {
                if (in_array((string) $key, $this->sensitiveFields, true)) {
                    $clean[$key] = $value;
                    continue;
                }

                $clean[$key] = trim(strip_tags($value));
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }
}
