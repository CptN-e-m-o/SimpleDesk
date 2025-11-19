<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Support\Facades\Request;
use Illuminate\View\Factory;
use Illuminate\View\View;

class TeamsController extends Controller
{
    public function index(Request $request): Factory|View
    {
        $teams = Team::all();
        $perPage = 10;
        $options = [10, 20, 50];

        return view('admin.teams.index', compact('teams', 'perPage', 'options'));
    }
}
