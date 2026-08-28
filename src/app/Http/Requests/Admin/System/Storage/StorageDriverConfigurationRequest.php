<?php

namespace App\Http\Requests\Admin\System\Storage;

use Illuminate\Foundation\Http\FormRequest;

class StorageDriverConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'driver' => ['required', 'string', 'max:50'], 'infrastructure_connection_id' => ['nullable', 'integer', 'min:1'], 'configuration' => ['nullable', 'array'], 'configuration.prefix' => ['nullable', 'string', 'max:255'], 'is_enabled' => ['required', 'boolean']];
    }
}
