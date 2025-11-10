<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AgentsController extends Controller
{
    const PAGINATE_PER_PAGE = 10;

    public function index(Request $request)
    {
        $sortableColumns = ['login', 'email', 'last_name'];
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        if (! in_array($sortBy, $sortableColumns)) {
            $sortBy = 'created_at';
        }

        $perPage = $request->input('per_page', self::PAGINATE_PER_PAGE);

        $agents = User::whereIn('role_id', [UserRole::Agent, UserRole::Admin])
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);

        $agents->appends($request->query());

        if ($request->ajax()) {
            return view('admin.agents-list.partials.agent_table', compact('agents', 'perPage', 'sortBy', 'sortDirection'))->render();
        }

        return view('admin.agents-list.index', compact('agents', 'perPage', 'sortBy', 'sortDirection'));
    }
}
