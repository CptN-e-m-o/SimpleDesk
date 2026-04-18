<?php

namespace App\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:2'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Please enter a reply.',
            'message.min' => 'Reply must be at least 2 characters.',
        ];
    }
}
