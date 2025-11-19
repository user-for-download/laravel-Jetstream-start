<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Jetstream;

use App\Actions\Jetstream\RemoveTeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Jetstream\Events\TeamMemberRemoved;
use Tests\TestCase;

class RemoveTeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_removes_team_member(): void
    {
        Event::fake([TeamMemberRemoved::class]);

        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;
        $member = User::factory()->create();

        $team->users()->attach($member, ['role' => 'editor']);

        $removeTeamMember = app(RemoveTeamMember::class);
        $removeTeamMember->remove($owner, $team, $member);

        $this->assertFalse($team->fresh()->hasUser($member));

        Event::assertDispatched(TeamMemberRemoved::class);
    }

    public function test_unauthorized_user_cannot_remove_team_member(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        $team->users()->attach($member1, ['role' => 'editor']);
        $team->users()->attach($member2, ['role' => 'editor']);

        $removeTeamMember = app(RemoveTeamMember::class);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        // Member1 trying to remove Member2 (neither is owner, not removing self)
        $removeTeamMember->remove($member1, $team, $member2);
    }

    public function test_prevents_removing_team_owner(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $removeTeamMember = app(RemoveTeamMember::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $removeTeamMember->remove($owner, $team, $owner);
    }

    public function test_member_can_leave_team(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;
        $member = User::factory()->create();

        $team->users()->attach($member, ['role' => 'editor']);

        $removeTeamMember = app(RemoveTeamMember::class);

        // Member removing themselves
        $removeTeamMember->remove($member, $team, $member);

        $this->assertFalse($team->fresh()->hasUser($member));
    }
}
