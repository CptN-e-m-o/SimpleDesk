<?php

namespace App\Http\Requests\Admin\AgentStatuses;

use App\Enums\Admin\AgentStatusScope;
use App\Enums\Admin\AgentWorkChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetAgentStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['status_id' => ['required','integer','exists:agent_statuses,id'], 'scope' => ['required', Rule::enum(AgentStatusScope::class)], 'channel' => ['nullable', Rule::enum(AgentWorkChannel::class), 'required_if:scope,channel'], 'duration_minutes' => ['nullable','integer','min:1','max:43200','prohibited_with:expires_at'], 'expires_at' => ['nullable','date','after:now','before_or_equal:'.now()->addDays(30)->toDateTimeString(),'prohibited_with:duration_minutes'], 'note' => ['nullable','string','max:1000']]; }
}
