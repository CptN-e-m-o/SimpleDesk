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
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'driver' => [
                'required',
                'string',
                'max:50',
            ],

            'infrastructure_connection_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'configuration' => [
                'nullable',
                'array',
            ],

            /*
             * There must be exactly one source of truth.
             */
            'configuration.infrastructure_connection_id' => [
                'prohibited',
            ],

            'is_enabled' => [
                'required',
                'boolean',
            ],
        ];
    }
}
