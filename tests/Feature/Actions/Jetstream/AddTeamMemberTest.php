<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Jetstream;

use App\Actions\Jetstream\AddTeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Jetstream\Events\TeamMemberAdded;
use Tests\TestCase;

class AddTeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_adds_team_member(): void
    {
        Event::fake([TeamMemberAdded::class]);

        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;
        $newMember = User::factory()->create();

        $addTeamMember = app(AddTeamMember::class);
        $addTeamMember->add($owner, $team, $newMember->email, 'editor');

        $this->assertTrue($team->fresh()->hasUser($newMember));

        Event::assertDispatched(fn (TeamMemberAdded $teamMemberAdded): bool => $teamMemberAdded->team->id === $team->id &&
            $teamMemberAdded->user->id === $newMember->id);
    }

    public function test_prevents_duplicate_members(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;
        $member = User::factory()->create();

        $team->users()->attach($member, ['role' => 'editor']);

        $addTeamMember = app(AddTeamMember::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $addTeamMember->add($owner, $team, $member->email, 'editor');
    }
}
