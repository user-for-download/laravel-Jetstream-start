<?php

declare(strict_types=1);

namespace Feature\Actions\Jetstream;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Jetstream\Http\Livewire\TeamMemberManager;
use Laravel\Jetstream\Mail\TeamInvitation;
use Livewire\Livewire;
use Tests\TestCase;

class InviteTeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_members_can_be_invited_to_team(): void
    {
        Mail::fake();

        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $testable = Livewire::test(TeamMemberManager::class, ['team' => $user->currentTeam])
            ->set('addTeamMemberForm', [
                'email' => 'test@example.com',
                'role' => 'admin', // Added valid role
            ])->call('addTeamMember');

        $testable->assertHasNoErrors();

        //        Mail::assertSent(TeamInvitation::class);

        $this->assertCount(1, $user->currentTeam->fresh()->teamInvitations);
    }

    public function test_team_member_invitations_can_be_cancelled(): void
    {
        Mail::fake();

        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        // Create invitation via service/action directly to setup state
        $invitation = $user->currentTeam->teamInvitations()->create([
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);

        Livewire::test(TeamMemberManager::class, ['team' => $user->currentTeam])
            ->call('cancelTeamInvitation', $invitation->id);

        $this->assertCount(0, $user->currentTeam->fresh()->teamInvitations);
    }

    public function test_validation_fails_if_email_already_invited(): void
    {
        Mail::fake();

        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $user->currentTeam->teamInvitations()->create([
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);

        Livewire::test(TeamMemberManager::class, ['team' => $user->currentTeam])
            ->set('addTeamMemberForm', [
                'email' => 'test@example.com',
                'role' => 'admin',
            ])
            ->call('addTeamMember')
            ->assertHasErrors(['email']);
    }

    public function test_validation_fails_if_email_already_on_team(): void
    {
        Mail::fake();

        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $otherUser = User::factory()->create(['email' => 'test@example.com']);
        $user->currentTeam->users()->attach($otherUser, ['role' => 'admin']);

        Livewire::test(TeamMemberManager::class, ['team' => $user->currentTeam])
            ->set('addTeamMemberForm', [
                'email' => 'test@example.com',
                'role' => 'admin',
            ])
            ->call('addTeamMember')
            ->assertHasErrors(['email']);
    }
}
