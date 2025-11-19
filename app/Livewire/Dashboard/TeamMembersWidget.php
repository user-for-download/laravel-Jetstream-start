<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class TeamMembersWidget extends Component
{
    public Collection $members;

    public int $totalMembers = 0;

    public function mount(): void
    {
        $team = auth()->user()->currentTeam;

        if ($team) {
            // Ensure uniqueness by ID to prevent Owner appearing twice
            $allMembers = $team->allUsers()->unique('id');

            $this->totalMembers = $allMembers->count();
            $this->members = $allMembers->take(5);
        } else {
            $this->members = collect();
            $this->totalMembers = 0;
        }
    }

    public function render(): View
    {
        return view('livewire.dashboard.team-members-widget');
    }
}
