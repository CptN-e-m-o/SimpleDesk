<?php

namespace App\Http\Requests\Admin\Agents;

class StoreAgentRequest extends AgentRequest
{
    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
