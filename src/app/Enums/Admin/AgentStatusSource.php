<?php

namespace App\Enums\Admin;

enum AgentStatusSource: string
{
    case Self = 'self';
    case Admin = 'admin';
    case System = 'system';
    case Api = 'api';
}
