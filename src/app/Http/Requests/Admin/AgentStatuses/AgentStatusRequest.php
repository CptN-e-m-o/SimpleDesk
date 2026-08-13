<?php

namespace App\Http\Requests\Admin\AgentStatuses;

use App\Enums\Admin\AgentRoutingEligibility;
use App\Enums\Admin\AgentStatusAvailability;
use App\Models\Admin\AgentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:500'], 'availability' => ['required', Rule::enum(AgentStatusAvailability::class)], 'routing_eligibility' => ['required', Rule::enum(AgentRoutingEligibility::class)], 'icon' => ['required', Rule::in(AgentStatus::ICONS)], 'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'default_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:43200'], 'is_active' => ['required', 'boolean'], 'is_selectable' => ['required', 'boolean'], 'is_default' => ['required', 'boolean'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000']];
    }
}
