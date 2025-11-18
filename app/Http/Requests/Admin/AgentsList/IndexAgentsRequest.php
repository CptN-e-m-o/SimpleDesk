<?php

namespace App\Http\Requests\Admin\AgentsList;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class IndexAgentsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sort_by' => ['sometimes', 'in:'.implode(',', User::SORTABLE)],
            'sort_direction' => ['sometimes', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'in:10,20,50'],
        ];
    }
}
