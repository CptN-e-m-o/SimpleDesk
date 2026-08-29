<?php

namespace App\Http\Requests\Admin\System;

use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\InfrastructureHealthStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InfrastructureConnectionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'type' => [
                'nullable',
                Rule::enum(
                    InfrastructureConnectionType::class,
                ),
            ],

            'source' => [
                'nullable',
                Rule::enum(
                    InfrastructureConnectionSource::class,
                ),
            ],

            'state' => [
                'nullable',
                Rule::in([
                    'enabled',
                    'disabled',
                ]),
            ],

            'health' => [
                'nullable',
                Rule::enum(
                    InfrastructureHealthStatus::class,
                ),
            ],

            'archived' => [
                'nullable',
                Rule::in([
                    'active',
                    'archived',
                    'all',
                ]),
            ],
        ];
    }
}
