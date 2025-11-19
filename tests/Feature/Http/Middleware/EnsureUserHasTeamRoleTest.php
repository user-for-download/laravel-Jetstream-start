<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\EnsureUserHasTeamRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class EnsureUserHasTeamRoleTest extends TestCase
{
    use RefreshDatabase;

    private EnsureUserHasTeamRole $ensureUserHasTeamRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureUserHasTeamRole = new EnsureUserHasTeamRole();
    }

    public function test_allows_request_when_user_has_required_role(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $mock = \Mockery::spy($user);
        $mock->shouldReceive('getAttribute')
            ->with('currentTeam')
            ->andReturn($user->currentTeam);
        $mock->shouldReceive('hasCurrentTeamRole')
            ->with('admin')
            ->andReturn(true);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $mock);

        $response = $this->ensureUserHasTeamRole->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200),
            'admin'
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_allows_request_when_user_has_one_of_multiple_roles(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $mock = \Mockery::spy($user);
        $mock->shouldReceive('getAttribute')
            ->with('currentTeam')
            ->andReturn($user->currentTeam);
        $mock->shouldReceive('hasCurrentTeamRole')
            ->with('admin')
            ->andReturn(false);
        $mock->shouldReceive('hasCurrentTeamRole')
            ->with('editor')
            ->andReturn(true);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $mock);

        $response = $this->ensureUserHasTeamRole->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200),
            'admin',
            'editor'
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_aborts_when_user_not_authenticated(): void
    {
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn (): null => null);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('User not authenticated or no current team');

        $this->ensureUserHasTeamRole->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200),
            'admin'
        );
    }

    public function test_aborts_when_user_has_no_current_team(): void
    {
        $user = User::factory()->create();

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('User not authenticated or no current team');

        $this->ensureUserHasTeamRole->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200),
            'admin'
        );
    }

    public function test_aborts_when_user_lacks_all_required_roles(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $mock = \Mockery::spy($user);
        $mock->shouldReceive('getAttribute')
            ->with('currentTeam')
            ->andReturn($user->currentTeam);
        $mock->shouldReceive('hasCurrentTeamRole')
            ->with('admin')
            ->andReturn(false);
        $mock->shouldReceive('hasCurrentTeamRole')
            ->with('editor')
            ->andReturn(false);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $mock);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('User does not have required team role. Required: [admin, editor]');

        $this->ensureUserHasTeamRole->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200),
            'admin',
            'editor'
        );
    }

    public function test_aborts_with_single_role_in_error_message(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $mock = \Mockery::spy($user);
        $mock->shouldReceive('getAttribute')
            ->with('currentTeam')
            ->andReturn($user->currentTeam);
        $mock->shouldReceive('hasCurrentTeamRole')
            ->with('admin')
            ->andReturn(false);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $mock);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('User does not have required team role. Required: [admin]');

        $this->ensureUserHasTeamRole->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200),
            'admin'
        );
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
