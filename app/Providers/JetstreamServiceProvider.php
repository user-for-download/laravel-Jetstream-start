<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Jetstream\AddTeamMember;
use App\Actions\Jetstream\CreateTeam;
use App\Actions\Jetstream\DeleteTeam;
use App\Actions\Jetstream\DeleteUser;
use App\Actions\Jetstream\InviteTeamMember;
use App\Actions\Jetstream\RemoveTeamMember;
use App\Actions\Jetstream\UpdateTeamName;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configurePermissions();

        Jetstream::createTeamsUsing(CreateTeam::class);
        Jetstream::updateTeamNamesUsing(UpdateTeamName::class);
        Jetstream::addTeamMembersUsing(AddTeamMember::class);
        Jetstream::inviteTeamMembersUsing(InviteTeamMember::class);
        Jetstream::removeTeamMembersUsing(RemoveTeamMember::class);
        Jetstream::deleteTeamsUsing(DeleteTeam::class);
        Jetstream::deleteUsersUsing(DeleteUser::class);
    }

    protected function configurePermissions(): void
    {
        Jetstream::defaultApiTokenPermissions([PermissionEnum::READ->value]);

        // Admin Role
        Jetstream::role(
            RoleEnum::ADMIN->value,
            RoleEnum::ADMIN->getLabel(),
            RoleEnum::ADMIN->getPermissions()
        )->description(RoleEnum::ADMIN->getDescription());

        // Editor Role
        Jetstream::role(
            RoleEnum::EDITOR->value,
            RoleEnum::EDITOR->getLabel(),
            RoleEnum::EDITOR->getPermissions()
        )->description(RoleEnum::EDITOR->getDescription());

        // Viewer Role
        Jetstream::role(
            RoleEnum::VIEWER->value,
            RoleEnum::VIEWER->getLabel(),
            RoleEnum::VIEWER->getPermissions()
        )->description(RoleEnum::VIEWER->getDescription());
    }
}
