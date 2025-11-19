<?php

namespace App\Http\Requests\Admin\Team;

use Illuminate\Foundation\Http\FormRequest;

class TeamIndexRequest extends FormRequest
{
    const PAGINATE_PER_PAGE = 15;

    public function rules(): array
    {
        return [
            'sort_by' => ['sometimes', 'string', 'in:id,name,is_active,created_at,owner_id,members_count'],
            'sort_direction' => ['sometimes', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'search' => ['sometimes', 'string', 'nullable', 'max:255'],
            'is_active' => ['sometimes', 'nullable', 'in:0,1'],
            'owner' => ['sometimes', 'string', 'nullable', 'max:255'],
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
        return (int) $this->input('per_page', self::PAGINATE_PER_PAGE);
    }

    public function filters(): array
    {
        return [
            'search' => $this->input('search'),
            'is_active' => $this->filled('is_active') ? (bool) $this->input('is_active') : null,
            'owner' => $this->input('owner'),
        ];
    }
}
