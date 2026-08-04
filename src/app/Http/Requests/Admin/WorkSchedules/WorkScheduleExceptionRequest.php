<?php

namespace App\Http\Requests\Admin\WorkSchedules;

use App\Enums\Admin\WorkScheduleExceptionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkScheduleExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['intervals' => $this->input('intervals', [])]);
    }

    public function rules(): array
    {
        return ['date' => ['required', 'date'], 'type' => ['required', Rule::enum(WorkScheduleExceptionType::class)], 'reason' => ['nullable', 'string', 'max:500'], 'intervals' => ['array'], 'intervals.*.starts_at' => ['required', 'date_format:H:i'], 'intervals.*.ends_at' => ['required', 'date_format:H:i'], 'intervals.*.ends_next_day' => ['required', 'boolean']];
    }
}
