<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use App\DataTransferObjects\Team\AddTeamMemberDto;
use App\Http\Requests\BaseFormRequest;
use App\Models\Team;
use App\Rules\UniqueTeamMember;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Rules\Role;

final class AddTeamMemberRequest extends BaseFormRequest
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
            'email' => ['required', 'email', 'exists:users', new UniqueTeamMember($team)],
            'role' => Jetstream::hasRoles()
                ? ['required', 'string', new Role()]
                : null,
        ]);
    }

    public function toDto(): AddTeamMemberDto
    {
        return AddTeamMemberDto::fromRequest($this->validated());
    }

    public function messages(): array
    {
        return [
            'email.exists' => 'We were unable to find a registered user with this email address.',
            'role.required' => 'Please select a role for the team member.',
        ];
    }
}
