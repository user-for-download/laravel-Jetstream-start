<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\DataTransferObjects\Team\InviteTeamMemberDto;
use App\Models\Team;
use App\Models\User;
use App\Rules\UniqueTeamMember;
use App\Services\Team\TeamServiceInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\Contracts\InvitesTeamMembers;
use Laravel\Jetstream\Events\InvitingTeamMember;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Mail\TeamInvitation;

final readonly class InviteTeamMember implements InvitesTeamMembers
{
    public function __construct(
        private TeamServiceInterface $teamService
    ) {}

    public function invite(User $user, Team $team, string $email, ?string $role = null): void
    {
        Gate::forUser($user)->authorize('addTeamMember', $team);

        $this->validate($team, $email, $role);

        event(new InvitingTeamMember($team, $email, $role));

        $inviteTeamMemberDto = new InviteTeamMemberDto(email: $email, role: $role);

        $this->teamService->inviteTeamMember($team, $inviteTeamMemberDto);

        // 2. Send Email (Infrastructure/Notification concern handled here)
        //        Mail::to($email)->send(new TeamInvitation($invitation));
    }

    private function validate(Team $team, string $email, ?string $role): void
    {
        Validator::make([
            'email' => $email,
            'role' => $role,
        ], [
            'email' => [
                'required',
                'email',
                Rule::unique(Jetstream::teamInvitationModel())->where(function (Builder $builder) use ($team): void {
                    $builder->where('team_id', $team->id);
                }),
                new UniqueTeamMember($team),
            ],
            'role' => Jetstream::hasRoles() ? ['required', 'string', new \Laravel\Jetstream\Rules\Role()] : [],
        ], [
            'email.unique' => __('This user has already been invited to the team.'),
        ])->validate();
    }
}
