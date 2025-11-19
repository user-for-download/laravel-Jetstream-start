<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Models\Team;
use App\Models\User;
use App\Services\Team\TeamServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TransferTeamOwnershipForm extends Component
{
    public Team $team;

    /**
     * The ID of the team member to transfer ownership to.
     */
    public string|int $transferToUserId = '';

    /**
     * The "confirming transfer" state.
     */
    public bool $confirmingTransfer = false;

    public function mount(Team $team): void
    {
        $this->team = $team;
    }

    public function confirmTransfer(): void
    {
        $this->resetErrorBag();

        if (!$this->transferToUserId) {
            $this->addError('transferToUserId', 'Please select a team member to transfer ownership to.');

            return;
        }

        $this->confirmingTransfer = true;
    }

    public function transferOwnership(TeamServiceInterface $teamService): void
    {
        $this->resetErrorBag();

        if ($this->team->personal_team) {
            $this->addError('team', 'You cannot transfer ownership of a personal team.');

            return;
        }

        if (!Auth::user()->ownsTeam($this->team)) {
            $this->addError('team', 'You do not have permission to transfer ownership of this team.');

            return;
        }

        $newOwner = User::find($this->transferToUserId);

        if (!$newOwner || !$this->team->hasUser($newOwner)) {
            $this->addError('transferToUserId', 'The selected user is not a member of this team.');

            return;
        }

        $teamService->transferOwnership($this->team, $newOwner);

        $this->confirmingTransfer = false;

        // Redirect to dashboard as the user is no longer the owner and permissions might have changed
        $this->redirectRoute('dashboard');
    }

    public function render(): View
    {
        return view('teams.transfer-team-ownership-form', [
            // Get all members excluding the current owner
            'potentialOwners' => $this->team->allUsers()->reject(fn ($user): bool => $user->id === $this->team->owner->id),
        ]);
    }
}
