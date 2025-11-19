<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Team;

use App\Http\Requests\Team\UpdateTeamNameRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTeamNameRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true_when_user_can_update_team(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $updateTeamNameRequest = UpdateTeamNameRequest::create(
            '/teams/'.$team->id,
            'PUT',
            ['name' => 'New Team Name']
        );

        $updateTeamNameRequest->setUserResolver(fn () => $owner);
        $updateTeamNameRequest->setRouteResolver(fn (): \Illuminate\Routing\Route => $this->createRoute($team));

        $this->assertTrue($updateTeamNameRequest->authorize());
    }

    public function test_authorize_returns_false_when_user_cannot_update_team(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $otherUser = User::factory()->create();

        $updateTeamNameRequest = UpdateTeamNameRequest::create(
            '/teams/'.$team->id,
            'PUT',
            ['name' => 'New Team Name']
        );

        $updateTeamNameRequest->setUserResolver(fn () => $otherUser);
        $updateTeamNameRequest->setRouteResolver(fn (): \Illuminate\Routing\Route => $this->createRoute($team));

        $this->assertFalse($updateTeamNameRequest->authorize());
    }

    public function test_rules_validates_name_field(): void
    {
        $updateTeamNameRequest = new UpdateTeamNameRequest();

        $rules = $updateTeamNameRequest->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:255', $rules['name']);
    }

    public function test_to_dto_creates_update_team_name_dto(): void
    {
        $updateTeamNameRequest = new UpdateTeamNameRequest();
        $updateTeamNameRequest->replace(['name' => 'Updated Team']);

        $validator = \Validator::make(
            $updateTeamNameRequest->all(),
            ['name' => 'required|string']
        );
        $updateTeamNameRequest->setValidator($validator);

        $updateTeamNameDto = $updateTeamNameRequest->toDto();

        $this->assertEquals('Updated Team', $updateTeamNameDto->name);
    }

    private function createRoute(Team $team): \Illuminate\Routing\Route
    {
        $route = new \Illuminate\Routing\Route('PUT', '/teams/{team}', []);
        $route->bind(new \Illuminate\Http\Request());
        $route->setParameter('team', $team);

        return $route;
    }
}
