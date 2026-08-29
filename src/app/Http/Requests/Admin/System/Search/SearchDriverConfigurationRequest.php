<?php

namespace App\Http\Requests\Admin\System\Search;

use Illuminate\Foundation\Http\FormRequest;

class SearchDriverConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'driver' => ['required', 'string', 'max:50'], 'infrastructure_connection_id' => ['nullable', 'integer', 'min:1'], 'configuration' => ['nullable', 'array', 'max:0'], 'is_enabled' => ['required', 'boolean']];
    }
}
