<?php

namespace App\Http\Requests\Admin\Agent;

use Illuminate\Foundation\Http\FormRequest;

class AgentIndexRequest extends FormRequest
{
    const PAGINATE_PER_PAGE = 10;

    public function rules(): array
    {
        return [
            'sort_by' => ['sometimes', 'string'],
            'sort_direction' => ['sometimes', 'in:asc,desc'],
            'status' => ['sometimes', 'nullable'],
            'per_page' => ['sometimes', 'integer'],
            'search' => ['sometimes', 'string', 'nullable'],
        ];
    }

    public function sortBy(): string
    {
        return $this->input('sort_by', 'created_at');
    }

    public function direction(): string
    {
        return $this->input('sort_direction', 'desc');
    }

    public function perPage(): int
    {
        return $this->input('per_page', self::PAGINATE_PER_PAGE);
    }

    public function filters(): array
    {
        return [
            'status' => $this->input('status'),
            'search' => $this->input('search'),
        ];
    }
}
