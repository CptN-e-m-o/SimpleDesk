<?php

namespace App\Http\Requests\Admin\System\Broadcasting;

use Illuminate\Foundation\Http\FormRequest;

class BroadcastDriverConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'driver' => ['required', 'string', 'max:50'], 'infrastructure_connection_id' => ['required', 'integer', 'min:1'], 'configuration' => ['nullable', 'array', 'max:0'], 'configuration.infrastructure_connection_id' => ['prohibited'], 'is_enabled' => ['required', 'boolean']];
    }
}
