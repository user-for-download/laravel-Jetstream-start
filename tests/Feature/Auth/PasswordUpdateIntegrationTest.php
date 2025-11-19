<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordUpdateIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_password_update_flow(): void
    {
        // Create user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('old-password-123'),
        ]);

        // Login
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'old-password-123',
        ]);

        $this->assertAuthenticatedAs($user);

        // Update password via Livewire
        Livewire::actingAs($user)
            ->test(\Laravel\Jetstream\Http\Livewire\UpdatePasswordForm::class)
            ->set('state.current_password', 'old-password-123')
            ->set('state.password', 'new-password-456')
            ->set('state.password_confirmation', 'new-password-456')
            ->call('updatePassword')
            ->assertHasNoErrors();

        // Verify password changed
        $this->assertTrue(
            Hash::check('new-password-456', $user->fresh()->password)
        );

        // Logout
        $this->post('/logout');
        $this->assertGuest();

        // Try logging in with old password (should fail)
        $testResponse = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'old-password-123',
        ]);

        $this->assertGuest();
        $testResponse->assertSessionHasErrors();

        // Login with new password (should succeed)
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'new-password-456',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_password_update_does_not_affect_other_users(): void
    {
        $user1 = User::factory()->create([
            'email' => 'user1@example.com',
            'password' => Hash::make('user1-password'),
        ]);

        $user2 = User::factory()->create([
            'email' => 'user2@example.com',
            'password' => Hash::make('user2-password'),
        ]);

        // User 1 updates their password
        Livewire::actingAs($user1)
            ->test(\Laravel\Jetstream\Http\Livewire\UpdatePasswordForm::class)
            ->set('state.current_password', 'user1-password')
            ->set('state.password', 'user1-new-password')
            ->set('state.password_confirmation', 'user1-new-password')
            ->call('updatePassword');

        // Verify user1's password changed
        $this->assertTrue(
            Hash::check('user1-new-password', $user1->fresh()->password)
        );

        // Verify user2's password did NOT change
        $this->assertTrue(
            Hash::check('user2-password', $user2->fresh()->password)
        );

        // Important: Logout user1 first
        $this->post('/logout');
        $this->assertGuest();

        // Now login as User 2 with their original password
        $testResponse = $this->post('/login', [
            'email' => 'user2@example.com',
            'password' => 'user2-password',
        ]);

        $testResponse->assertRedirect('/dashboard');

        // Refresh the user2 instance from database before checking auth
        $this->assertAuthenticatedAs($user2->fresh());
    }

    public function test_multiple_users_can_update_passwords_independently(): void
    {
        $users = User::factory()->count(3)->create([
            'password' => Hash::make('original-password'),
        ]);

        foreach ($users as $index => $user) {
            $newPassword = 'new-password-'.$index;

            Livewire::actingAs($user)
                ->test(\Laravel\Jetstream\Http\Livewire\UpdatePasswordForm::class)
                ->set('state.current_password', 'original-password')
                ->set('state.password', $newPassword)
                ->set('state.password_confirmation', $newPassword)
                ->call('updatePassword');

            $this->assertTrue(
                Hash::check($newPassword, $user->fresh()->password)
            );
        }
    }
}
