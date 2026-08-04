<?php

namespace App\Http\Requests\Admin\WorkSchedules;

use Illuminate\Foundation\Http\FormRequest;

class WorkScheduleAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['user_ids' => ['required', 'array', 'min:1'], 'user_ids.*' => ['integer', 'distinct', 'exists:users,id'], 'effective_from' => ['required', 'date'], 'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from']];
    }
}
