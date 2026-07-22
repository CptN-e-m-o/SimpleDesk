<?php

namespace App\Http\Requests\Admin\Agents;

class UpdateAgentRequest extends AgentRequest
{
    public function rules(): array
    {
        return [
            ...$this->baseRules($this->route('agent')->getKey()),
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
