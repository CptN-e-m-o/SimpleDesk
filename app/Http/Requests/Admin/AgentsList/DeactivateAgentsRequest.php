<?php

namespace App\Http\Requests\Admin\AgentsList;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeactivateAgentsRequest extends FormRequest
{
    public function rules(): array
    {
        $agentIds = json_decode($this->input('selected_agents', '[]'));

        return [
            'selected_agents' => 'required|json',
            'requested_tickets_action' => 'required|string|in:close,change_requester',
            'assigned_tickets_action' => 'required|string|in:unassign',
            'new_requester_id' => [
                'required_if:requested_tickets_action,change_requester',
                'exists:users,id',
                Rule::notIn($agentIds),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'new_requester_id.not_in' => 'Вы не можете переназначить заявки деактивируемому пользователю.',
        ];
    }
}
