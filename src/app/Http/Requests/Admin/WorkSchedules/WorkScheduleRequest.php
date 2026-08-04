<?php

namespace App\Http\Requests\Admin\WorkSchedules;

use App\Enums\Admin\Weekday;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active'), 'intervals' => $this->input('intervals', []), 'agent_ids' => $this->input('agent_ids', [])]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'], 'description' => ['nullable', 'string', 'max:5000'],
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())], 'is_active' => ['required', 'boolean'],
            'intervals' => ['required', 'array', 'min:1'], 'intervals.*.day_of_week' => ['required', Rule::enum(Weekday::class)],
            'intervals.*.starts_at' => ['required', 'date_format:H:i'], 'intervals.*.ends_at' => ['required', 'date_format:H:i'], 'intervals.*.ends_next_day' => ['required', 'boolean'],
            'agent_ids' => ['array'], 'agent_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'effective_from' => ['nullable', 'required_with:agent_ids', 'date'], 'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
