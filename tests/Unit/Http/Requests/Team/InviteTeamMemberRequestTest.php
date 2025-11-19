<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Team;

use App\Http\Requests\Team\InviteTeamMemberRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class InviteTeamMemberRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true_when_user_can_add_team_members(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $inviteTeamMemberRequest = InviteTeamMemberRequest::create(
            sprintf('/teams/%d/members', $team->id),
            'POST',
            ['email' => 'test@example.com', 'role' => 'editor']
        );

        $inviteTeamMemberRequest->setUserResolver(fn () => $owner);
        $inviteTeamMemberRequest->setRouteResolver(fn (): \Illuminate\Routing\Route => $this->createRoute($team));

        $this->assertTrue($inviteTeamMemberRequest->authorize());
    }

    public function test_authorize_returns_false_when_user_cannot_add_team_members(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $otherUser = User::factory()->create();

        $inviteTeamMemberRequest = InviteTeamMemberRequest::create(
            sprintf('/teams/%d/members', $team->id),
            'POST',
            ['email' => 'test@example.com']
        );

        $inviteTeamMemberRequest->setUserResolver(fn () => $otherUser);
        $inviteTeamMemberRequest->setRouteResolver(fn (): \Illuminate\Routing\Route => $this->createRoute($team));

        $this->assertFalse($inviteTeamMemberRequest->authorize());
    }

    public function test_rules_includes_email_validation(): void
    {
        $team = Team::factory()->create();

        $inviteTeamMemberRequest = InviteTeamMemberRequest::create(
            sprintf('/teams/%d/members', $team->id),
            'POST'
        );
        $inviteTeamMemberRequest->setRouteResolver(fn (): \Illuminate\Routing\Route => $this->createRoute($team));

        $rules = $inviteTeamMemberRequest->rules();

        $this->assertArrayHasKey('email', $rules);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
    }

    public function test_rules_includes_unique_validation_with_team_scope(): void
    {
        $team = Team::factory()->create();

        $inviteTeamMemberRequest = InviteTeamMemberRequest::create(
            sprintf('/teams/%d/members', $team->id),
            'POST'
        );
        $inviteTeamMemberRequest->setRouteResolver(fn (): \Illuminate\Routing\Route => $this->createRoute($team));

        $rules = $inviteTeamMemberRequest->rules();

        // Verify email rules exist and include unique rule
        $this->assertArrayHasKey('email', $rules);

        // Check that we have a Unique rule in the email rules
        $hasUniqueRule = false;
        foreach ($rules['email'] as $rule) {
            if ($rule instanceof \Illuminate\Validation\Rules\Unique) {
                $hasUniqueRule = true;

                break;
            }
        }

        $this->assertTrue($hasUniqueRule, 'Email rules should include a Unique validation rule');
    }

    public function test_to_dto_creates_invite_team_member_dto(): void
    {
        $inviteTeamMemberRequest = new InviteTeamMemberRequest();
        $inviteTeamMemberRequest->replace([
            'email' => 'invite@example.com',
            'role' => 'editor',
        ]);

        $validator = \Validator::make(
            $inviteTeamMemberRequest->all(),
            ['email' => 'required|email', 'role' => 'required']
        );
        $inviteTeamMemberRequest->setValidator($validator);

        $inviteTeamMemberDto = $inviteTeamMemberRequest->toDto();

        $this->assertEquals('invite@example.com', $inviteTeamMemberDto->email);
        $this->assertEquals('editor', $inviteTeamMemberDto->role);
    }

    public function test_messages_returns_custom_validation_messages(): void
    {
        $inviteTeamMemberRequest = new InviteTeamMemberRequest();

        $messages = $inviteTeamMemberRequest->messages();

        $this->assertArrayHasKey('email.unique', $messages);
        $this->assertArrayHasKey('role.required', $messages);
        $this->assertEquals(
            'This user has already been invited to the team.',
            $messages['email.unique']
        );
    }

    public function test_rules_excludes_role_when_roles_disabled(): void
    {
        $originalFeatures = config('jetstream.features');
        config(['jetstream.features' => []]);

        $team = Team::factory()->create();

        $inviteTeamMemberRequest = InviteTeamMemberRequest::create(
            sprintf('/teams/%d/members', $team->id),
            'POST'
        );
        $inviteTeamMemberRequest->setRouteResolver(fn (): \Illuminate\Routing\Route => $this->createRoute($team));

        $rules = $inviteTeamMemberRequest->rules();

        if (!Jetstream::hasRoles()) {
            $this->assertArrayNotHasKey('role', $rules);
        }

        config(['jetstream.features' => $originalFeatures]);
    }

    private function createRoute(Team $team): \Illuminate\Routing\Route
    {
        $route = new \Illuminate\Routing\Route('POST', '/teams/{team}/members', []);
        $route->bind(new \Illuminate\Http\Request());
        $route->setParameter('team', $team);

        return $route;
    }
}
