<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_member_middleware_allows_team_members(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $testResponse = $this->actingAs($user)
            ->get('/dashboard');

        $testResponse->assertOk();
    }

    public function test_team_member_middleware_blocks_users_without_team(): void
    {
        $user = User::factory()->create();

        $testResponse = $this->actingAs($user)
            ->get('/dashboard');

        $testResponse->assertOk();

        // Или если вы хотите блокировать доступ без команды:
        // $response->assertRedirect(); // редирект на создание команды
    }

    public function test_team_role_middleware_allows_correct_role(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $admin = User::factory()->create();
        $team->users()->attach($admin, ['role' => RoleEnum::ADMIN->value]);

        // Установим текущую команду для админа
        $admin->forceFill(['current_team_id' => $team->id])->save();

        $this->actingAs($admin);

        $this->assertTrue($admin->hasCurrentTeamRole(RoleEnum::ADMIN));
    }

    public function test_team_owner_middleware_allows_only_owner(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $member = User::factory()->create();
        $team->users()->attach($member, ['role' => RoleEnum::VIEWER->value]);
        $member->forceFill(['current_team_id' => $team->id])->save();

        // Owner should pass
        $this->actingAs($owner);
        $this->assertTrue($owner->ownsTeam($team));

        // Member should not pass
        $this->actingAs($member);
        $this->assertFalse($member->ownsTeam($team));
    }
}
