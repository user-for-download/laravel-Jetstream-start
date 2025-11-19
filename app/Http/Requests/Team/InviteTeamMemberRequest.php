<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use App\DataTransferObjects\Team\InviteTeamMemberDto;
use App\Http\Requests\BaseFormRequest;
use App\Models\Team;
use App\Rules\UniqueTeamMember;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Rules\Role;

final class InviteTeamMemberRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');

        return $team instanceof Team &&
            $this->user() !== null &&
            $this->user()->can('addTeamMember', $team);
    }

    public function rules(): array
    {
        $team = $this->route('team');

        return array_filter([
            'email' => [
                'required',
                'email',
                Rule::unique(Jetstream::teamInvitationModel())
                    ->where(fn (Builder $builder) => $builder->where('team_id', $team->id)),
                new UniqueTeamMember($team),
            ],
            'role' => Jetstream::hasRoles()
                ? ['required', 'string', new Role()]
                : null,
        ]);
    }

    public function toDto(): InviteTeamMemberDto
    {
        return InviteTeamMemberDto::fromRequest($this->validated());
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This user has already been invited to the team.',
            'role.required' => 'Please select a role for the invited member.',
        ];
    }
}
