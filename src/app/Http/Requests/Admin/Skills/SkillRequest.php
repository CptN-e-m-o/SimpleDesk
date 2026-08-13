<?php

namespace App\Http\Requests\Admin\Skills;

use App\Enums\Admin\Skills\SkillMatchType;
use App\Services\Admin\Skills\SkillRuleFieldRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'match_type' => ['required', Rule::enum(SkillMatchType::class)],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'rules' => ['required', 'array', 'min:1'],
            'rules.*.field_key' => ['required', 'string'],
            'rules.*.operator' => ['required', 'string'],
            'rules.*.value' => ['nullable'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $registry = app(SkillRuleFieldRegistry::class);

            foreach ($this->input('rules', []) as $index => $rule) {
                $field = $registry->field((string) ($rule['field_key'] ?? ''));

                if (! $field) {
                    $validator->errors()->add("rules.{$index}.field_key", 'The selected ticket field is not supported.');

                    continue;
                }

                $operator = (string) ($rule['operator'] ?? '');

                if (! in_array($operator, $field['operators'], true)) {
                    $validator->errors()->add("rules.{$index}.operator", 'This operator is not supported for the selected field.');

                    continue;
                }

                $error = $registry->validateValue($field, $operator, $rule['value'] ?? null);

                if ($error) {
                    $validator->errors()->add("rules.{$index}.value", $error);
                }
            }
        }];
    }
}
