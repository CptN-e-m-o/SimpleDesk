<?php

namespace Tests\Unit\Admin\Skills;

use App\Services\Admin\Skills\SkillRuleFieldRegistry;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class SkillRuleFieldRegistryTest extends TestCase
{
    use DatabaseMigrations;

    public function test_registry_only_exposes_real_ticket_fields(): void
    {
        $keys = collect(app(SkillRuleFieldRegistry::class)->schema())->pluck('key')->all();

        $this->assertSame(['priority', 'source', 'department_id'], $keys);
        $this->assertNotContains('ticket_form_id', $keys);
        $this->assertNotContains('location_id', $keys);
        $this->assertNotContains('type_id', $keys);
    }

    public function test_registry_validates_single_multiple_and_empty_values(): void
    {
        $registry = app(SkillRuleFieldRegistry::class);
        $field = $registry->field('priority');

        $this->assertNull($registry->validateValue($field, 'equals', 'high'));
        $this->assertNotNull($registry->validateValue($field, 'equals', 'unknown'));
        $this->assertNull($registry->validateValue($field, 'in', ['high', 'urgent']));
        $this->assertNotNull($registry->validateValue($field, 'in', []));
        $this->assertNull($registry->validateValue($field, 'is_empty', null));
    }
}
