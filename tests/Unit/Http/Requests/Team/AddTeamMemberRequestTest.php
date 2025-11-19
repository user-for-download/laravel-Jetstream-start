<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Team;

use App\Http\Requests\Team\AddTeamMemberRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class AddTeamMemberRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_messages_returns_custom_validation_messages(): void
    {
        $addTeamMemberRequest = new AddTeamMemberRequest();

        $messages = $addTeamMemberRequest->messages();

        $this->assertArrayHasKey('email.exists', $messages);
        $this->assertArrayHasKey('role.required', $messages);
        $this->assertEquals(
            'We were unable to find a registered user with this email address.',
            $messages['email.exists']
        );
        $this->assertEquals(
            'Please select a role for the team member.',
            $messages['role.required']
        );
    }

    public function test_rules_includes_role_when_roles_enabled(): void
    {
        $team = Team::factory()->create();

        $addTeamMemberRequest = AddTeamMemberRequest::create(
            sprintf('/teams/%d/members', $team->id),
            'POST'
        );
        $addTeamMemberRequest->setRouteResolver(fn (): \Illuminate\Routing\Route => $this->createRoute($team));

        $rules = $addTeamMemberRequest->rules();

        if (Jetstream::hasRoles()) {
            $this->assertArrayHasKey('role', $rules);
            $this->assertIsArray($rules['role']);
        }
    }

    public function test_rules_excludes_role_when_roles_disabled(): void
    {
        $originalFeatures = config('jetstream.features');
        config(['jetstream.features' => []]);

        $team = Team::factory()->create();

        $addTeamMemberRequest = AddTeamMemberRequest::create(
            sprintf('/teams/%d/members', $team->id),
            'POST'
        );
        $addTeamMemberRequest->setRouteResolver(fn (): \Illuminate\Routing\Route => $this->createRoute($team));

        $rules = $addTeamMemberRequest->rules();

        // After array_filter, null values should be removed
        if (!Jetstream::hasRoles()) {
            $this->assertArrayNotHasKey('role', $rules);
        }

        config(['jetstream.features' => $originalFeatures]);
    }

    public function test_authorize_returns_true_when_user_can_add_members(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $addTeamMemberRequest = AddTeamMemberRequest::create(
            sprintf('/teams/%d/members', $team->id),
            'POST',
            ['email' => 'test@example.com']
        );

        $addTeamMemberRequest->setUserResolver(fn () => $owner);
        $addTeamMemberRequest->setRouteResolver(fn (): \Illuminate\Routing\Route => $this->createRoute($team));

        $this->assertTrue($addTeamMemberRequest->authorize());
    }

    public function test_authorize_returns_false_when_user_cannot_add_members(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $otherUser = User::factory()->create();

        $addTeamMemberRequest = AddTeamMemberRequest::create(
            sprintf('/teams/%d/members', $team->id),
            'POST',
            ['email' => 'test@example.com']
        );

        $addTeamMemberRequest->setUserResolver(fn () => $otherUser);
        $addTeamMemberRequest->setRouteResolver(fn (): \Illuminate\Routing\Route => $this->createRoute($team));

        $this->assertFalse($addTeamMemberRequest->authorize());
    }

    public function test_to_dto_creates_add_team_member_dto(): void
    {
        $addTeamMemberRequest = new AddTeamMemberRequest();
        $addTeamMemberRequest->replace([
            'email' => 'member@example.com',
            'role' => 'editor',
        ]);

        $validator = \Validator::make(
            $addTeamMemberRequest->all(),
            ['email' => 'required|email', 'role' => 'required']
        );
        $addTeamMemberRequest->setValidator($validator);

        $addTeamMemberDto = $addTeamMemberRequest->toDto();

        $this->assertEquals('member@example.com', $addTeamMemberDto->email);
        $this->assertEquals('editor', $addTeamMemberDto->role);
    }

    public function test_rules_includes_email_validation(): void
    {
        $team = Team::factory()->create();

        $addTeamMemberRequest = AddTeamMemberRequest::create(
            sprintf('/teams/%d/members', $team->id),
            'POST'
        );
        $addTeamMemberRequest->setRouteResolver(fn (): \Illuminate\Routing\Route => $this->createRoute($team));

        $rules = $addTeamMemberRequest->rules();

        $this->assertArrayHasKey('email', $rules);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
        $this->assertContains('exists:users', $rules['email']);
    }

    private function createRoute(Team $team): \Illuminate\Routing\Route
    {
        $route = new \Illuminate\Routing\Route('POST', '/teams/{team}/members', []);
        $route->bind(new \Illuminate\Http\Request());
        $route->setParameter('team', $team);

        return $route;
    }
}
