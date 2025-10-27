<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ];

        if ($this->user()->isAdminOrAgent()) {
            $rules['priority_id'] = 'required|exists:priorities,id';
            $rules['assigned_agent_id'] = 'nullable|exists:users,id';
        }

        return $rules;
    }
}
