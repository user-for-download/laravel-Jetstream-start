<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class RecentActivity extends Component
{
    public Collection $activities;

    public function mount(): void
    {
        $this->activities = collect([
            [
                'id' => 1,
                'type' => 'team_member_added',
                'description' => 'John Doe was added to the team',
                'user' => auth()->user()->name,
                'created_at' => now()->subHours(2),
                'icon' => 'user-add',
                'color' => 'blue',
            ],
            [
                'id' => 2,
                'type' => 'project_created',
                'description' => 'New project "Website Redesign" was created',
                'user' => auth()->user()->name,
                'created_at' => now()->subHours(5),
                'icon' => 'folder-add',
                'color' => 'green',
            ],
            [
                'id' => 3,
                'type' => 'role_updated',
                'description' => 'User role was updated to Editor',
                'user' => 'Admin',
                'created_at' => now()->subDay(),
                'icon' => 'shield',
                'color' => 'yellow',
            ],
        ]);
    }

    public function render(): View
    {
        return view('livewire.dashboard.recent-activity');
    }
}
