<?php

namespace App\Http\Requests\Admin\Teams;

use App\Support\Teams\TeamDepartmentOptions;
use App\Support\Teams\TeamEligibleUsers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $eligibleUserIds = app(TeamEligibleUsers::class)->ids();
        $departmentIds = app(TeamDepartmentOptions::class)->ids();

        return [
            'name' => ['required', 'string', 'max:255'],

            'departments' => ['nullable', 'array'],
            'departments.*' => ['integer', Rule::in($departmentIds)],

            'is_active' => ['required', 'boolean'],
            'admin_notes' => ['nullable', 'string'],

            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => ['integer', Rule::in($eligibleUserIds)],

            'lead_user_id' => ['nullable', 'integer', Rule::in($eligibleUserIds)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $memberIds = collect($this->input('member_ids', []))
                ->map(fn ($id) => (int) $id)
                ->unique();

            $leadUserId = $this->input('lead_user_id');

            if ($leadUserId !== null && $leadUserId !== '' && ! $memberIds->contains((int) $leadUserId)) {
                $validator->errors()->add(
                    'lead_user_id',
                    'The selected team lead must also be a team member.'
                );
            }
        });
    }
}
