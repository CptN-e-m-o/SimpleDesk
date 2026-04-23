<?php

namespace App\Services\Admin;

use App\Models\Admin\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartmentService
{
    public function create(array $data): Department
    {
        return DB::transaction(function () use ($data) {
            if ($data['is_default']) {
                Department::query()->update(['is_default' => false]);
            }

            $department = Department::query()->create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'type' => $data['type'],
                'business_hours' => $data['business_hours'] ?? null,
                'outgoing_email' => $data['outgoing_email'] ?? null,
                'department_status_id' => $data['department_status_id'] ?: null,
                'signature' => $data['signature'] ?? null,
                'is_default' => $data['is_default'],
            ]);

            $this->syncRelations($department, $data);

            return $department;
        });
    }

    public function update(Department $department, array $data): Department
    {
        return DB::transaction(function () use ($department, $data) {
            if ($data['is_default']) {
                Department::query()
                    ->whereKeyNot($department->id)
                    ->update(['is_default' => false]);
            }

            $department->update([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'type' => $data['type'],
                'business_hours' => $data['business_hours'] ?? null,
                'outgoing_email' => $data['outgoing_email'] ?? null,
                'department_status_id' => $data['department_status_id'] ?: null,
                'signature' => $data['signature'] ?? null,
                'is_default' => $data['is_default'],
            ]);

            $this->syncRelations($department, $data);

            return $department;
        });
    }

    protected function syncRelations(Department $department, array $data): void
    {
        $department->teams()->sync($data['team_ids'] ?? []);

        $managers = collect($data['manager_ids'] ?? [])
            ->mapWithKeys(fn ($userId) => [
                $userId => ['is_manager' => true],
            ])
            ->all();

        $department->users()->sync($managers);
    }
}
