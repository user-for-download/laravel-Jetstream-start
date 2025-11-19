<?php

// ========== tests/Feature/TeamRolesTest.php ==========

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_has_all_permissions(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $admin = User::factory()->create();
        $team->users()->attach($admin, ['role' => RoleEnum::ADMIN->value]);

        $this->assertTrue($admin->hasTeamPermission($team, PermissionEnum::CREATE->value));
        $this->assertTrue($admin->hasTeamPermission($team, PermissionEnum::READ->value));
        $this->assertTrue($admin->hasTeamPermission($team, PermissionEnum::UPDATE->value));
        $this->assertTrue($admin->hasTeamPermission($team, PermissionEnum::DELETE->value));
    }

    public function test_editor_cannot_delete(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $editor = User::factory()->create();
        $team->users()->attach($editor, ['role' => RoleEnum::EDITOR->value]);

        $this->assertTrue($editor->hasTeamPermission($team, PermissionEnum::CREATE->value));
        $this->assertTrue($editor->hasTeamPermission($team, PermissionEnum::READ->value));
        $this->assertTrue($editor->hasTeamPermission($team, PermissionEnum::UPDATE->value));
        $this->assertFalse($editor->hasTeamPermission($team, PermissionEnum::DELETE->value));
    }

    public function test_viewer_can_only_read(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $viewer = User::factory()->create();
        $team->users()->attach($viewer, ['role' => RoleEnum::VIEWER->value]);

        $this->assertFalse($viewer->hasTeamPermission($team, PermissionEnum::CREATE->value));
        $this->assertTrue($viewer->hasTeamPermission($team, PermissionEnum::READ->value));
        $this->assertFalse($viewer->hasTeamPermission($team, PermissionEnum::UPDATE->value));
        $this->assertFalse($viewer->hasTeamPermission($team, PermissionEnum::DELETE->value));
    }

    public function test_owner_is_always_admin(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        // ИСПРАВЛЕНО: используем isAdminOfTeam вместо isTeamAdmin
        $this->assertTrue($owner->isAdminOfTeam($team));
        $this->assertTrue($team->isOwner($owner));
    }

    public function test_team_can_helper_works(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $this->actingAs($owner);

        $this->assertTrue(team_can(PermissionEnum::CREATE));
        $this->assertTrue(team_can(PermissionEnum::DELETE));
    }

    public function test_is_team_admin_helper_works(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $this->actingAs($owner);

        $this->assertTrue(is_team_admin());
        $this->assertTrue(is_team_owner());
    }

    public function test_admin_role_is_admin(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $admin = User::factory()->create();
        $team->users()->attach($admin, ['role' => RoleEnum::ADMIN->value]);

        // Admin (не владелец) тоже должен быть админом команды
        $this->assertTrue($admin->isAdminOfTeam($team));
        $this->assertFalse($admin->ownsTeam($team)); // но не владельцем
    }
}
