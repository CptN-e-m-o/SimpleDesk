<?php

namespace Database\Seeders\Permissions\Agent\AgentPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAgentToolsSeeder extends Seeder
{
    public function run(): void
    {
        $group = $this->createOrUpdateGroup();

        foreach ($this->permissions() as $permission) {
            $this->createOrUpdatePermission($group->id, $permission);
        }
    }

    private function createOrUpdateGroup(): PermissionGroup
    {
        return PermissionGroup::updateOrCreate(
            [
                'key' => 'tools',
                'panel' => 'agent',
                'type' => 'agent',
            ],
            [
                'label' => 'Tools',
                'sort_order' => 30,
            ]
        );
    }

    private function permissions(): array
    {
        return [
            ...$this->cannedResponsePermissions(),
            ...$this->knowledgeBasePermissions(),
            ...$this->categoryPermissions(),
            ...$this->articlePermissions(),
            ...$this->templatePermissions(),
            ...$this->pagePermissions(),
            ...$this->commentPermissions(),
            ...$this->recurringTicketPermissions(),
        ];
    }

    private function cannedResponsePermissions(): array
    {
        return [
            [
                'key' => 'agent.tools.canned_responses',
                'label' => 'Canned responses',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.tools.access_all_canned_responses',
                'label' => 'Access all canned responses',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.canned_responses',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.tools.create_edit_canned_responses',
                'label' => 'Create or edit canned responses',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.canned_responses',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.tools.delete_canned_responses',
                'label' => 'Delete canned responses',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.canned_responses',
                'sort_order' => 40,
            ],
        ];
    }

    private function knowledgeBasePermissions(): array
    {
        return [
            [
                'key' => 'agent.tools.knowledge_base',
                'label' => 'Knowledge Base',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 50,
            ],
            [
                'key' => 'agent.tools.settings',
                'label' => 'Settings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.knowledge_base',
                'sort_order' => 240,
            ],
        ];
    }

    private function categoryPermissions(): array
    {
        return [
            [
                'key' => 'agent.tools.categories',
                'label' => 'Categories',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.knowledge_base',
                'sort_order' => 60,
            ],
            [
                'key' => 'agent.tools.add_or_edit_categories',
                'label' => 'Add or edit categories',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.categories',
                'sort_order' => 70,
            ],
            [
                'key' => 'agent.tools.delete_categories',
                'label' => 'Delete categories',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.categories',
                'sort_order' => 80,
            ],
        ];
    }

    private function articlePermissions(): array
    {
        return [
            [
                'key' => 'agent.tools.articles',
                'label' => 'Articles',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.knowledge_base',
                'sort_order' => 90,
            ],
            [
                'key' => 'agent.tools.access_all_articles',
                'label' => 'Access all articles',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.articles',
                'sort_order' => 100,
            ],
            [
                'key' => 'agent.tools.add_or_edit_articles',
                'label' => 'Add or edit articles',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.articles',
                'sort_order' => 110,
            ],
            [
                'key' => 'agent.tools.publish_article',
                'label' => 'Publish article',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.add_or_edit_articles',
                'sort_order' => 120,
            ],
            [
                'key' => 'agent.tools.delete_article',
                'label' => 'Delete an article',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.articles',
                'sort_order' => 130,
            ],
        ];
    }

    private function templatePermissions(): array
    {
        return [
            [
                'key' => 'agent.tools.templates',
                'label' => 'Templates',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.articles',
                'sort_order' => 140,
            ],
            [
                'key' => 'agent.tools.add_edit_template',
                'label' => 'Add or edit template',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.templates',
                'sort_order' => 150,
            ],
            [
                'key' => 'agent.tools.delete_templates',
                'label' => 'Delete templates',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.templates',
                'sort_order' => 160,
            ],
        ];
    }

    private function pagePermissions(): array
    {
        return [
            [
                'key' => 'agent.tools.pages',
                'label' => 'Pages',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.knowledge_base',
                'sort_order' => 170,
            ],
            [
                'key' => 'agent.tools.add_or_edit_pages',
                'label' => 'Add or edit pages',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.pages',
                'sort_order' => 180,
            ],
            [
                'key' => 'agent.tools.publish_page',
                'label' => 'Publish page',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.add_or_edit_pages',
                'sort_order' => 190,
            ],
            [
                'key' => 'agent.tools.delete_pages',
                'label' => 'Delete pages',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.pages',
                'sort_order' => 200,
            ],
        ];
    }

    private function commentPermissions(): array
    {
        return [
            [
                'key' => 'agent.tools.comments',
                'label' => 'Comments',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.knowledge_base',
                'sort_order' => 210,
            ],
            [
                'key' => 'agent.tools.approve_unapprove_comments',
                'label' => 'Approve or unapprove comments',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.comments',
                'sort_order' => 220,
            ],
            [
                'key' => 'agent.tools.delete_comments',
                'label' => 'Delete comments',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.comments',
                'sort_order' => 230,
            ],
        ];
    }

    private function recurringTicketPermissions(): array
    {
        return [
            [
                'key' => 'agent.tools.recurring_tickets',
                'label' => 'Recurring tickets',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 250,
            ],
            [
                'key' => 'agent.tools.add_edit_recurring_tickets',
                'label' => 'Add or edit recurring tickets',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.recurring_tickets',
                'sort_order' => 260,
            ],
            [
                'key' => 'agent.tools.delete_recurring_tickets',
                'label' => 'Delete recurring tickets',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tools.recurring_tickets',
                'sort_order' => 270,
            ],
        ];
    }

    private function createOrUpdatePermission(int $groupId, array $permission): void
    {
        $parentKey = $permission['parent_key'] ?? null;

        unset($permission['parent_key']);

        Permission::updateOrCreate(
            ['key' => $permission['key']],
            [
                ...$permission,
                'permission_group_id' => $groupId,
                'parent_id' => $this->resolveParentId($parentKey),
            ]
        );
    }

    private function resolveParentId(?string $parentKey): ?int
    {
        if (! $parentKey) {
            return null;
        }

        return Permission::where('key', $parentKey)->value('id');
    }
}
