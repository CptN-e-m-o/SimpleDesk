<?php

namespace App\Support\Teams;

class TeamDepartmentOptions
{
    public function options(): array
    {
        return [
            ['id' => 'support', 'name' => 'Support'],
            ['id' => 'sales', 'name' => 'Sales'],
            ['id' => 'billing', 'name' => 'Billing'],
            ['id' => 'technical', 'name' => 'Technical'],
        ];
    }

    public function ids(): array
    {
        return collect($this->options())
            ->pluck('id')
            ->all();
    }
}
