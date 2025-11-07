<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;

class AgentsController extends Controller
{
    const PAGINATE_PER_PAGE = 15;

    public function index()
    {
        $agents = User::whereIn('role_id', [UserRole::Agent, UserRole::Admin])
            ->paginate(self::PAGINATE_PER_PAGE);

        return view('admin.agents-list.index', compact('agents'));
    }
}
