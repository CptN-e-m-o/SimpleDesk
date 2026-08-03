<?php

namespace App\Services\Admin\Mail\Settings;

use App\Models\Admin\Mail\ReplyParsingRule;
use Illuminate\Support\Facades\DB;

class ReplyParsingRuleAdminService
{
    public function create(array $data): ReplyParsingRule
    {
        return DB::transaction(
            fn (): ReplyParsingRule => ReplyParsingRule::query()->create(
                $this->normalized($data)
            ),
            3,
        );
    }

    public function update(
        ReplyParsingRule $rule,
        array $data,
    ): ReplyParsingRule {
        return DB::transaction(
            function () use ($rule, $data): ReplyParsingRule {
                $rule = ReplyParsingRule::query()
                    ->lockForUpdate()
                    ->findOrFail($rule->id);

                $rule->fill($this->normalized($data))->save();

                return $rule->fresh();
            },
            3,
        );
    }

    public function delete(ReplyParsingRule $rule): void
    {
        DB::transaction(
            function () use ($rule): void {
                $rule = ReplyParsingRule::query()
                    ->lockForUpdate()
                    ->findOrFail($rule->id);

                $rule->forceFill(['is_active' => false])->save();
                $rule->delete();
            },
            3,
        );
    }

    public function restore(int $ruleId): ReplyParsingRule
    {
        return DB::transaction(
            function () use ($ruleId): ReplyParsingRule {
                $rule = ReplyParsingRule::onlyTrashed()
                    ->lockForUpdate()
                    ->findOrFail($ruleId);

                $rule->restore();
                $rule->forceFill(['is_active' => false])->save();

                return $rule->fresh();
            },
            3,
        );
    }

    public function forceDelete(int $ruleId): void
    {
        DB::transaction(
            function () use ($ruleId): void {
                ReplyParsingRule::onlyTrashed()
                    ->lockForUpdate()
                    ->findOrFail($ruleId)
                    ->forceDelete();
            },
            3,
        );
    }

    private function normalized(array $data): array
    {
        return [
            'name' => trim($data['name']),
            'pattern' => $data['pattern'],
            'pattern_type' => $data['pattern_type'],
            'content_type' => $data['content_type'],
            'display_order' => max(0, (int) $data['display_order']),
            'is_active' => (bool) $data['is_active'],
            'description' => $this->nullableString($data['description'] ?? null),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
