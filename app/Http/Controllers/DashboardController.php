<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\User\UserServiceInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService
    ) {}

    public function index(): View
    {
        $user = auth()->user();

        if ($user) {
            $this->userService->loadDashboardData($user);
        }

        $team = $user?->currentTeam;

        return view('dashboard', [
            'user' => $user,
            'team' => $team,
            'isTeamOwner' => $team && $user->ownsTeam($team),
            'permissions' => $team ? $user->teamPermissions($team) : [],
        ]);
    }

    public function owner(): View|Factory
    {
        return view('dashboard-owner');
    }

    public function admin(): View|Factory
    {
        return view('dashboard-owner');
    }
}
