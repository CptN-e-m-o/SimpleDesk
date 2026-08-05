<?php

namespace App\Enums\Admin;

enum AgentStatusEndReason: string { case Replaced = 'replaced'; case Expired = 'expired'; case Cleared = 'cleared'; case Administrative = 'administrative'; }
