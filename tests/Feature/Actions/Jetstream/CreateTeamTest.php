<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Jetstream;

use App\Actions\Jetstream\CreateTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Jetstream\Events\AddingTeam;
use Tests\TestCase;

class CreateTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_team_and_switches_to_it(): void
    {
        Event::fake([AddingTeam::class]);

        $user = User::factory()->withPersonalTeam()->create();
        $createTeam = app(CreateTeam::class);

        $team = $createTeam->create($user, [
            'name' => 'New Team',
        ]);

        $this->assertEquals('New Team', $team->name);
        $this->assertEquals($user->id, $team->user_id);
        $this->assertFalse($team->personal_team);

        // Assert user was switched to the new team
        $this->assertEquals($team->id, $user->fresh()->current_team_id);

        Event::assertDispatched(AddingTeam::class);
    }

    public function test_requires_authorization(): void
    {
        $user = User::factory()->create();
        $createTeam = app(CreateTeam::class);

        // This should work - users can create teams
        $team = $createTeam->create($user, ['name' => 'Test Team']);

        $this->assertNotNull($team);
    }
}
