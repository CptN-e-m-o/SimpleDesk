<?php

namespace App\Http\Requests\Tickets\User;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:ticket_categories,id'],
            'priority' => ['required', 'string', Rule::in(Ticket::priorities())],
            'service' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,doc,docx,txt'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'Please enter a subject.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'Selected category is invalid.',
            'priority.required' => 'Please select a priority.',
            'priority.in' => 'Selected priority is invalid.',
            'description.required' => 'Please provide a description.',
            'description.min' => 'Description must be at least 10 characters.',
            'attachments.*.max' => 'Each attachment must not exceed 5 MB.',
            'attachments.*.mimes' => 'Allowed file types: jpg, jpeg, png, pdf, doc, docx, txt.',
        ];
    }
}
