<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class SetOwnAgentStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['status_id' => ['required','integer','exists:agent_statuses,id'], 'duration_minutes' => ['nullable','integer','min:1','max:43200'], 'note' => ['nullable','string','max:1000']]; }
}
