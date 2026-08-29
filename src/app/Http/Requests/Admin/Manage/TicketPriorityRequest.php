<?php

namespace App\Http\Requests\Admin\Manage;

use App\Enums\Admin\Manage\CatalogVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => is_string($this->name) ? trim($this->name) : $this->name, 'description' => is_string($this->description) ? trim($this->description) ?: null : null, 'color' => is_string($this->color) ? strtoupper(trim($this->color)) : $this->color]);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:1000'], 'color' => ['required', 'regex:/^#[0-9A-F]{6}$/'], 'visibility' => ['required', Rule::enum(CatalogVisibility::class)], 'is_active' => ['required', 'boolean'], 'is_default' => ['required', 'boolean']];
    }
}
