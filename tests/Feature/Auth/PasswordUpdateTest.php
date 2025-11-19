<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('current-password-123'),
        ]);
    }

    public function test_password_can_be_updated(): void
    {
        $this->actingAs($this->user);

        Livewire::test(\Laravel\Jetstream\Http\Livewire\UpdatePasswordForm::class)
            ->set('state.current_password', 'current-password-123')
            ->set('state.password', 'new-password-456')
            ->set('state.password_confirmation', 'new-password-456')
            ->call('updatePassword');

        // Verify the password was actually changed
        $this->assertTrue(
            Hash::check('new-password-456', $this->user->fresh()->password)
        );
    }

    public function test_current_password_must_be_correct(): void
    {
        $this->actingAs($this->user);

        Livewire::test(\Laravel\Jetstream\Http\Livewire\UpdatePasswordForm::class)
            ->set('state.current_password', 'wrong-password')
            ->set('state.password', 'new-password-456')
            ->set('state.password_confirmation', 'new-password-456')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        // Verify password was NOT changed
        $this->assertTrue(
            Hash::check('current-password-123', $this->user->fresh()->password)
        );
    }

    public function test_new_password_must_be_confirmed(): void
    {
        $this->actingAs($this->user);

        Livewire::test(\Laravel\Jetstream\Http\Livewire\UpdatePasswordForm::class)
            ->set('state.current_password', 'current-password-123')
            ->set('state.password', 'new-password-456')
            ->set('state.password_confirmation', 'different-password')
            ->call('updatePassword')
            ->assertHasErrors(['password']);
    }

    public function test_current_password_is_required(): void
    {
        $this->actingAs($this->user);

        Livewire::test(\Laravel\Jetstream\Http\Livewire\UpdatePasswordForm::class)
            ->set('state.current_password', '')
            ->set('state.password', 'new-password-456')
            ->set('state.password_confirmation', 'new-password-456')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);
    }

    public function test_new_password_is_required(): void
    {
        $this->actingAs($this->user);

        Livewire::test(\Laravel\Jetstream\Http\Livewire\UpdatePasswordForm::class)
            ->set('state.current_password', 'current-password-123')
            ->set('state.password', '')
            ->set('state.password_confirmation', '')
            ->call('updatePassword')
            ->assertHasErrors(['password']);
    }

    public function test_password_confirmation_is_required(): void
    {
        $this->actingAs($this->user);

        Livewire::test(\Laravel\Jetstream\Http\Livewire\UpdatePasswordForm::class)
            ->set('state.current_password', 'current-password-123')
            ->set('state.password', 'new-password-456')
            ->set('state.password_confirmation', '')
            ->call('updatePassword')
            ->assertHasErrors(['password']);
    }

    public function test_password_must_meet_minimum_length_requirement(): void
    {
        $this->actingAs($this->user);

        Livewire::test(\Laravel\Jetstream\Http\Livewire\UpdatePasswordForm::class)
            ->set('state.current_password', 'current-password-123')
            ->set('state.password', 'short')
            ->set('state.password_confirmation', 'short')
            ->call('updatePassword')
            ->assertHasErrors(['password']);
    }

    public function test_unauthenticated_user_cannot_update_password(): void
    {
        // Guest users shouldn't be able to access the component
        $this->get(route('profile.show'))
            ->assertRedirect(route('login'));
    }

    public function test_password_update_shows_success_message(): void
    {
        $this->actingAs($this->user);

        Livewire::test(\Laravel\Jetstream\Http\Livewire\UpdatePasswordForm::class)
            ->set('state.current_password', 'current-password-123')
            ->set('state.password', 'new-password-456')
            ->set('state.password_confirmation', 'new-password-456')
            ->call('updatePassword')
            ->assertDispatched('saved');
    }
}
