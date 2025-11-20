<?php

namespace App\Services\Admin\Users;

class TeamService
{
    public function tableHead(): array
    {
        return [
            [
                'title' => __('lang.teams_list.name'),
                'column' => 'name',
                'sortable' => true,
            ],
            [
                'title' => __('lang.teams_list.team_size'),
                'column' => 'team_size',
                'sortable' => true,
            ],
            [
                'title' => __('lang.teams_list.status'),
                'column' => 'status',
                'sortable' => true,
            ],
            [
                'title' => __('lang.teams_list.lead'),
            ],
            [
                'title' => __('lang.teams_list.actions'),
            ],
        ];
    }
}
