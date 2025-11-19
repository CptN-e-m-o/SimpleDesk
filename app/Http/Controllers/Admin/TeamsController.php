<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class TeamsController extends Controller
{
    public function index()
    {
        return view('admin.teams.index');
    }
}
