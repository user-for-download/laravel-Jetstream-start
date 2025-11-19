<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Team;

use App\DataTransferObjects\Team\AddTeamMemberDto;
use App\DataTransferObjects\Team\CreateTeamDto;
use App\DataTransferObjects\Team\InviteTeamMemberDto;
use App\DataTransferObjects\Team\UpdateTeamNameDto;
use App\Models\Team;
use App\Models\User;
use App\Services\Team\TeamService;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Mail\TeamInvitation;
use Tests\TestCase;

class TeamServiceTest extends TestCase
{
    use RefreshDatabase;

    private TeamService $teamService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teamService = app(TeamService::class);
    }

    public function test_creates_team_successfully(): void
    {
        $user = User::factory()->create();
        $createTeamDto = new CreateTeamDto(name: 'Test Team', personalTeam: false);

        $team = $this->teamService->createTeam($user, $createTeamDto);

        $this->assertInstanceOf(Team::class, $team);
        $this->assertEquals('Test Team', $team->name);
        $this->assertEquals($user->id, $team->user_id);
        $this->assertFalse($team->personal_team);
    }

    public function test_creates_personal_team(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        $team = $this->teamService->createPersonalTeam($user);

        $this->assertInstanceOf(Team::class, $team);
        $this->assertTrue($team->personal_team);
        $this->assertEquals("John's Team", $team->name);
        $this->assertEquals($team->id, $user->fresh()->current_team_id);
    }

    public function test_updates_team_name(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);
        $updateTeamNameDto = new UpdateTeamNameDto(name: 'New Name');

        $this->teamService->updateTeamName($team, $updateTeamNameDto);

        $this->assertEquals('New Name', $team->fresh()->name);
    }

    public function test_adds_team_member_successfully(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create(['email' => 'member@test.com']);
        $team = Team::factory()->create(['user_id' => $owner->id]);
        $addTeamMemberDto = new AddTeamMemberDto(email: 'member@test.com', role: 'editor');

        $this->teamService->addTeamMember($team, $addTeamMemberDto);

        $team->refresh();

        $this->assertTrue($team->hasUser($member));
        $this->assertEquals('editor', $team->users->first()->membership->role);
    }

    public function test_throws_exception_when_adding_duplicate_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create(['email' => 'member@test.com']);
        $team = Team::factory()->create(['user_id' => $owner->id]);

        // Add member first time
        $team->users()->attach($member, ['role' => 'viewer']);

        // Try to add again
        $addTeamMemberDto = new AddTeamMemberDto(email: 'member@test.com', role: 'editor');

        try {
            $this->teamService->addTeamMember($team, $addTeamMemberDto);
            $this->fail('Expected exception was not thrown.');
        } catch (UniqueConstraintViolationException) {
            $this->assertTrue(true); // Exactly what we wanted
        } catch (QueryException $e) {
            // Fallback for drivers that might wrap it differently or for older Laravel versions
            // "Duplicate entry" is standard for MySQL/MariaDB, "23000" is the SQLSTATE
            $this->assertTrue(
                str_contains($e->getMessage(), 'Duplicate entry') ||
                $e->getCode() === '23000' ||
                str_contains($e->getMessage(), 'constraint violation') // Lowercase check
            );
        }
    }

    public function test_invites_team_member_and_sends_email(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $owner->id]);
        $inviteTeamMemberDto = new InviteTeamMemberDto(email: 'newmember@test.com', role: 'editor');

        $this->teamService->inviteTeamMember($team, $inviteTeamMemberDto);

        $team->refresh();

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => 'newmember@test.com',
            'role' => 'editor',
        ]);

        if (Features::enabled(Features::emailVerification())) {
            Mail::assertSent(TeamInvitation::class, static fn ($mail) => $mail->hasTo('newmember@test.com'));
        }
    }

    public function test_throws_exception_when_inviting_existing_member(): void
    {
        $this->markTestSkipped('Validation responsibility moved to FormRequest. Service layer delegates duplicate prevention to DB constraints.');
    }

    public function test_removes_team_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $owner->id]);
        $team->users()->attach($member, ['role' => 'editor']);

        $this->teamService->removeTeamMember($team, $member);

        $team->refresh();

        $this->assertFalse($team->hasUser($member));
    }

    public function test_throws_exception_when_owner_tries_to_leave(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $owner->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('You may not leave a team that you created.');

        $this->teamService->removeTeamMember($team, $owner);
    }

    public function test_deletes_team(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $owner->id]);
        $teamId = $team->id;

        $this->teamService->deleteTeam($team);

        $this->assertDatabaseMissing('teams', ['id' => $teamId]);
    }

    public function test_switches_team(): void
    {
        $user = User::factory()->create();
        $team1 = Team::factory()->create(['user_id' => $user->id]);
        $team2 = Team::factory()->create(['user_id' => $user->id]);

        $user->update(['current_team_id' => $team1->id]);

        $this->teamService->switchTeam($user, $team2);

        $this->assertEquals($team2->id, $user->fresh()->current_team_id);
    }

    public function test_throws_exception_when_switching_to_team_user_does_not_belong_to(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherTeam = Team::factory()->create(['user_id' => $otherUser->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('You do not belong to this team.');

        $this->teamService->switchTeam($user, $otherTeam);
    }
}
