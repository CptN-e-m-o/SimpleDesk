<?php

namespace App\Http\Requests\Admin\System\Queues;

use Illuminate\Foundation\Http\FormRequest;

class StoreQueueDriverConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'driver' => ['required', 'string', 'max:50'], 'configuration' => ['nullable', 'array'], 'is_enabled' => ['required', 'boolean']];
    }
}
