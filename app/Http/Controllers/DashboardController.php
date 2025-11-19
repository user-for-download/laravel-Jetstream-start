<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $team = $user->currentTeam;

        $data = [
            'user' => $user,
            'team' => $team,
            'isTeamOwner' => $team ? $user->ownsTeam($team) : false,
            'isTeamAdmin' => $team ? $user->isAdminOfTeam($team) : false,
            'permissions' => $team ? $user->teamPermissions($team) : [],
        ];

        return view('dashboard', $data);
    }

    public function owner(): View|Factory
    {
        return view('dashboard-owner');
    }

    public function admin(): View|Factory
    {
        return view('dashboard-owner'); // Assuming reusing the same view for demo
    }
}
