<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Illuminate\View\View;
use Livewire\Component;

class UserPermissions extends Component
{
    public array $permissions = [];

    public ?string $roleName = null;

    public ?string $roleDescription = null;

    public function mount(): void
    {
        $user = auth()->user();
        $team = $user->currentTeam;

        if ($team) {
            $this->permissions = $user->teamPermissions($team);
            $role = $user->teamRole($team);

            if ($role) {
                $this->roleName = $role->name;
                $this->roleDescription = $role->description;
            }
        }
    }

    public function render(): View
    {
        return view('livewire.dashboard.user-permissions');
    }
}
