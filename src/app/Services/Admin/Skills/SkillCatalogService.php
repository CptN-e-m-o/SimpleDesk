<?php

namespace App\Services\Admin\Skills;

use App\Models\Admin\Skill;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SkillCatalogService
{
    public function create(array $data, User $actor): Skill
    {
        return DB::transaction(function () use ($data, $actor): Skill {
            $skill = Skill::create([
                ...$this->attributes($data),
                'slug' => $this->uniqueSlug($data['name']),
                'version' => 1,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->syncRules($skill, $data['rules']);

            return $skill->load('rules');
        });
    }

    public function update(Skill $skill, array $data, User $actor): Skill
    {
        return DB::transaction(function () use ($skill, $data, $actor): Skill {
            $skill = Skill::query()->lockForUpdate()->findOrFail($skill->id);
            $rulesChanged = $this->normalizedRules($skill->rules()->get()->toArray())
                !== $this->normalizedRules($data['rules']);

            $skill->update([
                ...$this->attributes($data),
                'updated_by' => $actor->id,
                'version' => $rulesChanged ? $skill->version + 1 : $skill->version,
            ]);

            if ($rulesChanged) {
                $skill->rules()->delete();
                $this->syncRules($skill, $data['rules']);
            }

            return $skill->load('rules');
        });
    }

    public function duplicate(Skill $skill, User $actor): Skill
    {
        $skill->load('rules');

        return $this->create([
            ...$skill->only(['description', 'match_type', 'is_active', 'sort_order']),
            'name' => $skill->name.' Copy',
            'rules' => $skill->rules->map->only(['field_key', 'operator', 'value'])->all(),
        ], $actor);
    }

    public function setActive(Skill $skill, bool $active, User $actor): Skill
    {
        return DB::transaction(function () use ($skill, $active, $actor): Skill {
            $skill = Skill::query()->lockForUpdate()->findOrFail($skill->id);
            $skill->update(['is_active' => $active, 'updated_by' => $actor->id]);

            return $skill->refresh();
        });
    }

    public function archive(Skill $skill, User $actor): void
    {
        DB::transaction(function () use ($skill, $actor): void {
            $skill = Skill::query()->lockForUpdate()->findOrFail($skill->id);
            $skill->update(['updated_by' => $actor->id]);
            $skill->delete();
        });
    }

    public function restore(int $id, User $actor): Skill
    {
        return DB::transaction(function () use ($id, $actor): Skill {
            $skill = Skill::onlyTrashed()->lockForUpdate()->findOrFail($id);
            $skill->restore();
            $skill->update(['updated_by' => $actor->id]);

            return $skill->refresh();
        });
    }

    public function forceDelete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $skill = Skill::onlyTrashed()->lockForUpdate()->findOrFail($id);

            if (! $skill->trashed()) {
                throw ValidationException::withMessages(['skill' => 'Only archived skills can be permanently deleted.']);
            }

            $skill->forceDelete();
        });
    }

    private function attributes(array $data): array
    {
        return collect($data)->only(['name', 'description', 'match_type', 'is_active', 'sort_order'])->all();
    }

    private function syncRules(Skill $skill, array $rules): void
    {
        foreach ($rules as $index => $rule) {
            $skill->rules()->create([
                'subject_type' => 'ticket',
                'field_key' => $rule['field_key'],
                'operator' => $rule['operator'],
                'value' => $this->normalizeValue(
                    $rule['operator'],
                    $rule['value'] ?? null
                ),
                'sort_order' => $index,
            ]);
        }
    }

    private function normalizedRules(array $rules): array
    {
        return collect($rules)->map(fn (array $rule) => [
            'field_key' => $rule['field_key'],
            'operator' => $rule['operator'],
            'value' => $this->normalizeValue(
                $rule['operator'],
                $rule['value'] ?? null
            ),
        ])->values()->all();
    }

    private function normalizeValue(string $operator, mixed $value): ?array
    {
        if (in_array($operator, ['is_empty', 'is_not_empty', 'is_true', 'is_false'], true)) {
            return null;
        }

        return is_array($value) ? array_values($value) : [$value];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'skill';
        $slug = $base;
        $index = 2;

        while (Skill::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$index++;
        }

        return $slug;
    }
}
