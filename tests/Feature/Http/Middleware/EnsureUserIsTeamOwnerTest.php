<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\EnsureUserIsTeamOwner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class EnsureUserIsTeamOwnerTest extends TestCase
{
    use RefreshDatabase;

    private EnsureUserIsTeamOwner $ensureUserIsTeamOwner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureUserIsTeamOwner = new EnsureUserIsTeamOwner();
    }

    public function test_allows_request_when_user_owns_current_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $this->ensureUserIsTeamOwner->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200)
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_aborts_when_user_not_authenticated(): void
    {
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn (): null => null);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('User not authenticated or no current team');

        $this->ensureUserIsTeamOwner->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200)
        );
    }

    public function test_aborts_when_user_has_no_current_team(): void
    {
        $user = User::factory()->create();

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('User not authenticated or no current team');

        $this->ensureUserIsTeamOwner->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200)
        );
    }

    public function test_aborts_when_user_does_not_own_current_team(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $member = User::factory()->create();
        $member->current_team_id = $team->id;
        $member->save();

        $team->users()->attach($member, ['role' => 'editor']);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $member);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('User does not own the current team');

        $this->ensureUserIsTeamOwner->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200)
        );
    }

    public function test_allows_owner_but_not_member_of_same_team(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $member = User::factory()->create();
        $member->current_team_id = $team->id;
        $member->save();

        $team->users()->attach($member, ['role' => 'editor']);

        // Owner should pass
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $owner);

        $response = $this->ensureUserIsTeamOwner->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Owner Success', 200)
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Owner Success', $response->getContent());

        // Member should fail
        $memberRequest = Request::create('/test', 'GET');
        $memberRequest->setUserResolver(fn () => $member);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('User does not own the current team');

        $this->ensureUserIsTeamOwner->handle(
            $memberRequest,
            fn ($req): \Illuminate\Http\Response => new Response('Member Success', 200)
        );
    }
}
