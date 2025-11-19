<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\EnsureUserBelongsToTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class EnsureUserBelongsToTeamTest extends TestCase
{
    use RefreshDatabase;

    private EnsureUserBelongsToTeam $ensureUserBelongsToTeam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureUserBelongsToTeam = new EnsureUserBelongsToTeam();
    }

    public function test_allows_request_when_user_belongs_to_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $this->ensureUserBelongsToTeam->handle(
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
        $this->expectExceptionMessage('User not authenticated');

        $this->ensureUserBelongsToTeam->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200)
        );
    }

    public function test_aborts_when_user_has_no_current_team(): void
    {
        $user = User::factory()->create();
        // No team assigned

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('User does not belong to any team');

        $this->ensureUserBelongsToTeam->handle(
            $request,
            fn ($req): \Illuminate\Http\Response => new Response('Success', 200)
        );
    }
}
