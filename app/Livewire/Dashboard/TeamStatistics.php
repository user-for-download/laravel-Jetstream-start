<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Illuminate\View\View;
use Livewire\Component;

class TeamStatistics extends Component
{
    public int $totalMembers = 0;

    public int $totalProjects = 0;

    public int $pendingInvitations = 0;

    public int $activeProjects = 0;

    public function mount(): void
    {
        $team = auth()->user()->currentTeam;

        if ($team) {
            $this->totalMembers = $team->allUsers()->count();
            $this->pendingInvitations = $team->teamInvitations()->count();

            // Если у вас есть модель Project
            // $this->totalProjects = $team->projects()->count();
            // $this->activeProjects = $team->projects()->where('status', 'active')->count();
        }
    }

    public function render(): View
    {
        return view('livewire.dashboard.team-statistics');
    }
}
