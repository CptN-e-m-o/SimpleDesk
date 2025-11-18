<?php

namespace App\Http\Requests\Admin\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeactivateAgentsRequest extends FormRequest
{
    public function rules(): array
    {
        $agentIds = json_decode($this->input('selected_agents', '[]'), true) ?: [];

        return [
            'selected_agents' => ['required', 'json'],
            'requested_tickets_action' => ['required', 'string', 'in:close,change_requester'],
            'assigned_tickets_action' => ['required', 'string', 'in:unassign'],
            'new_requester_id' => [
                Rule::requiredIf($this->input('requested_tickets_action') === 'change_requester'),
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

    public function agentIds(): array
    {
        return json_decode($this->input('selected_agents', '[]'), true) ?: [];
    }

    public function requestedTicketsAction(): string
    {
        return $this->input('requested_tickets_action');
    }

    public function assignedTicketsAction(): string
    {
        return $this->input('assigned_tickets_action');
    }

    public function newRequesterId(): ?int
    {
        return $this->input('new_requester_id');
    }
}
