<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Fortify;

use App\Actions\Fortify\UpdateUserPassword;
use App\Models\User;
use App\Services\User\UserServiceInterface;
use App\Services\Validation\PasswordValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_validates_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        // Authenticate the user so current_password validation works
        $this->actingAs($user);

        $updateUserPassword = app(UpdateUserPassword::class);

        $this->expectException(ValidationException::class);

        $updateUserPassword->update($user, [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);
    }

    public function test_validates_password_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        // Authenticate the user
        $this->actingAs($user);

        $updateUserPassword = app(UpdateUserPassword::class);

        $this->expectException(ValidationException::class);

        $updateUserPassword->update($user, [
            'current_password' => 'correct-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'different-password',
        ]);
    }

    public function test_validates_password_rules(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        // Authenticate the user
        $this->actingAs($user);

        $updateUserPassword = app(UpdateUserPassword::class);

        $this->expectException(ValidationException::class);

        $updateUserPassword->update($user, [
            'current_password' => 'correct-password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);
    }

    public function test_uses_update_password_error_bag(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        // Authenticate the user
        $this->actingAs($user);

        $updateUserPassword = app(UpdateUserPassword::class);

        try {
            $updateUserPassword->update($user, [
                'current_password' => 'wrong-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $validationException) {
            $this->assertEquals('updatePassword', $validationException->errorBag);
        }
    }

    public function test_successfully_updates_password_when_valid(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        // Authenticate the user - THIS IS THE KEY FIX
        $this->actingAs($user);

        $updateUserPassword = app(UpdateUserPassword::class);

        $updateUserPassword->update($user, [
            'current_password' => 'correct-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        // Verify password was updated
        $this->assertTrue(
            Hash::check('new-password-123', $user->fresh()->password)
        );
    }

    public function test_calls_service_with_correct_dto(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        // Authenticate the user - THIS IS THE KEY FIX
        $this->actingAs($user);

        // Mock the service
        $serviceMock = Mockery::mock(UserServiceInterface::class);

        $serviceMock->shouldReceive('updatePassword')
            ->once()
            ->with(
                Mockery::on(fn ($u): bool => $u->id === $user->id),
                Mockery::on(fn ($dto): bool => $dto->currentPassword === 'correct-password'
                    && $dto->password === 'new-password-123'
                    && $dto->passwordConfirmation === 'new-password-123')
            );

        // Create action with mocked service
        $updateUserPassword = new UpdateUserPassword(
            $serviceMock,
            app(PasswordValidator::class)
        );

        $updateUserPassword->update($user, [
            'current_password' => 'correct-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        // Mockery will verify the expectations
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
