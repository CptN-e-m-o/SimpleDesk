<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\Admin\Users\TeamService;
use Illuminate\Support\Facades\Request;
use Illuminate\View\View;

class TeamsController extends Controller
{
    public function index(Request $request, TeamService $service): View
    {
        $teams = Team::paginate(10);
        $perPage = 10;
        $options = [10, 20, 50];
        $head = $service->tableHead();

        return view('admin.teams.index', compact('teams', 'perPage', 'options', 'head'));
    }

    public function create()
    {
        $agents = User::agents()->get();

        return view('admin.teams.create', compact('agents'));
    }
}
