<?php

namespace App\Http\Requests\Admin\Mail\Quarantine;

use Illuminate\Foundation\Http\FormRequest;

class IgnoreEmailQuarantineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('reason')) {
            $reason = trim(
                (string) $this->input('reason')
            );

            $this->merge([
                'reason' => $reason !== ''
                    ? $reason
                    : null,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
