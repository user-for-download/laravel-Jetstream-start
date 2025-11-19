<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\EnsureUserHasTeamPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class EnsureUserHasTeamPermissionTest extends TestCase
{
    use RefreshDatabase;

    private EnsureUserHasTeamPermission $ensureUserHasTeamPermission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureUserHasTeamPermission = new EnsureUserHasTeamPermission();
    }

    public function test_allows_request_when_user_has_required_permission(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        // Spy on the method
        $mock = \Mockery::spy($user);
        $mock->shouldReceive('getAttribute')
            ->with('currentTeam')
            ->andReturn($user->currentTeam);
        $mock->shouldReceive('canInCurrentTeam')
            ->with('create')
            ->andReturn(true);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $mock);

        $response = $this->ensureUserHasTeamPermission->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200),
            'create'
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_aborts_when_user_not_authenticated(): void
    {
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn (): null => null);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('User not authenticated or no current team');

        $this->ensureUserHasTeamPermission->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200),
            'create'
        );
    }

    public function test_aborts_when_user_has_no_current_team(): void
    {
        $user = User::factory()->create();

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('User not authenticated or no current team');

        $this->ensureUserHasTeamPermission->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200),
            'create'
        );
    }

    public function test_aborts_when_user_lacks_required_permission(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $mock = \Mockery::spy($user);
        $mock->shouldReceive('getAttribute')
            ->with('currentTeam')
            ->andReturn($user->currentTeam);
        $mock->shouldReceive('canInCurrentTeam')
            ->with('delete')
            ->andReturn(false);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $mock);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('User does not have required permission: delete');

        $this->ensureUserHasTeamPermission->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200),
            'delete'
        );
    }

    public function test_aborts_when_user_lacks_one_of_multiple_permissions(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $mock = \Mockery::spy($user);
        $mock->shouldReceive('getAttribute')
            ->with('currentTeam')
            ->andReturn($user->currentTeam);
        $mock->shouldReceive('canInCurrentTeam')
            ->with('create')
            ->andReturn(true);
        $mock->shouldReceive('canInCurrentTeam')
            ->with('delete')
            ->andReturn(false);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $mock);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('User does not have required permission: delete');

        $this->ensureUserHasTeamPermission->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200),
            'create',
            'delete'
        );
    }

    public function test_allows_request_when_user_has_all_required_permissions(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $mock = \Mockery::spy($user);
        $mock->shouldReceive('getAttribute')
            ->with('currentTeam')
            ->andReturn($user->currentTeam);
        $mock->shouldReceive('canInCurrentTeam')
            ->with('create')
            ->andReturn(true);
        $mock->shouldReceive('canInCurrentTeam')
            ->with('update')
            ->andReturn(true);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $mock);

        $response = $this->ensureUserHasTeamPermission->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200),
            'create',
            'update'
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
