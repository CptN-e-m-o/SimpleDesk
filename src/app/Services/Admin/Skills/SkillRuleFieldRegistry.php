<?php

namespace App\Services\Admin\Skills;

use App\Models\Admin\Department;
use App\Models\Ticket;
use App\Models\TicketPriority;

class SkillRuleFieldRegistry
{
    private const REFERENCE_OPERATORS = [
        'equals', 'not_equals', 'in', 'not_in', 'is_empty', 'is_not_empty',
    ];

    public function schema(): array
    {
        return [
            [
                'key' => 'priority',
                'label' => 'Priority',
                'type' => 'enum',
                'operators' => self::REFERENCE_OPERATORS,
                'multiple' => false,
                'options' => TicketPriority::query()->where('is_active', true)->orderBy('sort_order')->get(['slug', 'name'])->map(fn (TicketPriority $priority) => ['value' => $priority->slug, 'label' => $priority->name])->all(),
            ],
            $this->enumField(
                'source',
                'Source',
                Ticket::sources(),
                fn (string $value) => ucfirst($value)
            ),
            [
                'key' => 'department_id',
                'label' => 'Department',
                'type' => 'reference',
                'operators' => self::REFERENCE_OPERATORS,
                'multiple' => false,
                'options' => Department::query()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Department $department) => [
                        'value' => $department->id,
                        'label' => $department->name,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }

    public function field(string $key): ?array
    {
        foreach ($this->schema() as $field) {
            if ($field['key'] === $key) {
                return $field;
            }
        }

        return null;
    }

    public function validateValue(array $field, string $operator, mixed $value): ?string
    {
        if (in_array($operator, ['is_empty', 'is_not_empty', 'is_true', 'is_false'], true)) {
            return $value === null || $value === '' || $value === []
                ? null
                : 'This operator does not accept a value.';
        }

        if ($operator === 'between') {
            return is_array($value) && count($value) === 2
                ? null
                : 'Between requires exactly two values.';
        }

        if (in_array($operator, ['in', 'not_in'], true)) {
            if (! is_array($value) || $value === []) {
                return 'This operator requires at least one value.';
            }

            foreach ($value as $item) {
                if (! $this->optionExists($field, $item)) {
                    return 'One or more selected values are invalid.';
                }
            }

            return null;
        }

        if (is_array($value) || $value === null || $value === '') {
            return 'This operator requires one value.';
        }

        return $this->optionExists($field, $value)
            ? null
            : 'The selected value is invalid.';
    }

    private function enumField(string $key, string $label, array $values, callable $labeler): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'type' => 'enum',
            'operators' => self::REFERENCE_OPERATORS,
            'multiple' => false,
            'options' => array_map(
                fn (string $value) => ['value' => $value, 'label' => $labeler($value)],
                $values
            ),
        ];
    }

    private function optionExists(array $field, mixed $value): bool
    {
        if (! isset($field['options'])) {
            return true;
        }

        return collect($field['options'])->contains(
            fn (array $option) => (string) $option['value'] === (string) $value
        );
    }
}
