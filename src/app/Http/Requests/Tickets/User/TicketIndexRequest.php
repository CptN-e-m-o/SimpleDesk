<?php

namespace App\Http\Requests\Tickets\User;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => is_string($this->search) ? trim($this->search) : $this->search,
            'status' => $this->status === '' ? null : $this->status,
            'priority' => $this->priority === '' ? null : $this->priority,
        ]);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(Ticket::statuses())],
            'priority' => ['nullable', 'string', Rule::in(Ticket::priorities())],
        ];
    }
}
