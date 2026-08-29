<?php

namespace App\Http\Requests\Admin\System;

use Illuminate\Foundation\Http\FormRequest;

class SystemAuditLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'area' => [
                'nullable',
                'string',
                'max:100',
            ],

            'action' => [
                'nullable',
                'string',
                'max:100',
            ],

            'actor_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'created_from' => [
                'nullable',
                'date',
            ],

            'created_to' => [
                'nullable',
                'date',
                'after_or_equal:created_from',
            ],
        ];
    }
}
