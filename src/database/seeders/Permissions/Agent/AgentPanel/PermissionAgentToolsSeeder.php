<?php

namespace Database\Seeders\Permissions\Agent\AgentPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAgentToolsSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
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

        $permissions = [
            [
                'key' => 'agent.contacts.canned_responses',
                'label' => 'Canned responses',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.contacts.access_all_canned_responses',
                'label' => 'Access all canned responses',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.canned_responses',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.contacts.create_edit_canned_responses',
                'label' => 'Create or edit canned responses',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.canned_responses',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.contacts.delete_canned_responses',
                'label' => 'Delete the canned responses',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.canned_responses',
                'sort_order' => 40,
            ],
            [
                'key' => 'agent.contacts.knowledge_base',
                'label' => 'Knowledge Base',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 50,
            ],
            [
                'key' => 'agent.contacts.categories',
                'label' => 'Categories',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.knowledge_base',
                'sort_order' => 60,
            ],
            [
                'key' => 'agent.contacts.add_edit_categories',
                'label' => 'Add or edit categories',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.categories',
                'sort_order' => 70,
            ],
            [
                'key' => 'agent.contacts.delete_categories',
                'label' => 'Delete categories',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.categories',
                'sort_order' => 80,
            ],
            [
                'key' => 'agent.contacts.articles',
                'label' => 'Articles',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.knowledge_base',
                'sort_order' => 90,
            ],
            [
                'key' => 'agent.contacts.access_all_articles',
                'label' => 'Access all articles',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.articles',
                'sort_order' => 100,
            ],
            [
                'key' => 'agent.contacts.add_edit_articles',
                'label' => 'Add or edit articles',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.articles',
                'sort_order' => 110,
            ],
            [
                'key' => 'agent.contacts.publish_article',
                'label' => 'Publish article',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.add_or_edit_articles',
                'sort_order' => 120,
            ],
            [
                'key' => 'agent.contacts.delete_article',
                'label' => 'Delete an article',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.articles',
                'sort_order' => 130,
            ],
            [
                'key' => 'agent.contacts.templates',
                'label' => 'Templates',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.articles',
                'sort_order' => 140,
            ],
            [
                'key' => 'agent.contacts.add_edit_template',
                'label' => 'Add or edit template',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.templates',
                'sort_order' => 150,
            ],
            [
                'key' => 'agent.contacts.delete_templates',
                'label' => 'Delete templates',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.templates',
                'sort_order' => 160,
            ],
            [
                'key' => 'agent.contacts.pages',
                'label' => 'Pages',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.knowledge_base',
                'sort_order' => 170,
            ],
            [
                'key' => 'agent.contacts.add_edit_pages',
                'label' => 'Add or edit pages',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.pages',
                'sort_order' => 180,
            ],
            [
                'key' => 'agent.contacts.publish_page',
                'label' => 'Publish page',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.add_or_edit_articles',
                'sort_order' => 190,
            ],
            [
                'key' => 'agent.contacts.delete_pages',
                'label' => 'Delete pages',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.pages',
                'sort_order' => 200,
            ],
            [
                'key' => 'agent.contacts.comments',
                'label' => 'Comments',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.knowledge_base',
                'sort_order' => 210,
            ],
            [
                'key' => 'agent.contacts.approve_unapprove_comments',
                'label' => 'Approve or Un-approve comments',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.comments',
                'sort_order' => 220,
            ],
            [
                'key' => 'agent.contacts.delete_comments',
                'label' => 'Delete comments',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.comments',
                'sort_order' => 230,
            ],
            [
                'key' => 'agent.contacts.settings',
                'label' => 'Settings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.knowledge_base',
                'sort_order' => 240,
            ],
            [
                'key' => 'agent.contacts.recurring_tickets',
                'label' => 'Recurring tickets',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 250,
            ],
            [
                'key' => 'agent.contacts.add_edit_tickets',
                'label' => 'Add or edit tickets',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.recurring_tickets',
                'sort_order' => 260,
            ],
            [
                'key' => 'agent.contacts.delete_tickets',
                'label' => 'Delete tickets',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.recurring_tickets',
                'sort_order' => 270,
            ],
        ];

        foreach ($permissions as $permission) {
            $parentKey = $permission['parent_key'] ?? null;

            unset($permission['parent_key']);

            $parentId = null;

            if ($parentKey) {
                $parentId = Permission::where('key', $parentKey)->value('id');
            }

            Permission::updateOrCreate(
                ['key' => $permission['key']],
                [
                    ...$permission,
                    'permission_group_id' => $group->id,
                    'parent_id' => $parentId,
                ]
            );
        }
    }
}
