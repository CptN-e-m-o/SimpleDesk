<?php

namespace App\Http\Requests\Admin\System;

use Illuminate\Foundation\Http\FormRequest;

class StoreInfrastructureConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'type' => ['required', 'string', 'max:100'], 'source' => ['required', 'string', 'max:100'], 'configuration' => ['required', 'array'], 'credentials' => ['nullable', 'array'], 'is_enabled' => ['nullable', 'boolean']];
    }
}
