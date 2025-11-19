<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DataTransferObjects\User\UpdateUserPasswordDto;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserServicePasswordTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = app(UserService::class);
    }

    public function test_update_password_hashes_new_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $updateUserPasswordDto = new UpdateUserPasswordDto(
            currentPassword: 'old-password',
            password: 'new-password-123',
            passwordConfirmation: 'new-password-123'
        );

        $this->userService->updatePassword($user, $updateUserPasswordDto);

        $this->assertTrue(
            Hash::check('new-password-123', $user->fresh()->password)
        );
    }

    public function test_update_password_does_not_validate_current_password(): void
    {
        // Service should only update, validation happens in Action/Request
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $updateUserPasswordDto = new UpdateUserPasswordDto(
            currentPassword: 'wrong-password', // Service doesn't validate this
            password: 'new-password-123',
            passwordConfirmation: 'new-password-123'
        );

        $this->userService->updatePassword($user, $updateUserPasswordDto);

        // Password is updated regardless (validation is Action's responsibility)
        $this->assertTrue(
            Hash::check('new-password-123', $user->fresh()->password)
        );
    }

    public function test_reset_password_hashes_password(): void
    {
        $user = User::factory()->create();

        $resetUserPasswordDto = new \App\DataTransferObjects\User\ResetUserPasswordDto(
            password: 'reset-password-123',
            passwordConfirmation: 'reset-password-123'
        );

        $this->userService->resetPassword($user, $resetUserPasswordDto);

        $this->assertTrue(
            Hash::check('reset-password-123', $user->fresh()->password)
        );
    }

    public function test_password_is_stored_as_hash_not_plain_text(): void
    {
        $user = User::factory()->create();

        $updateUserPasswordDto = new UpdateUserPasswordDto(
            currentPassword: 'anything',
            password: 'new-password-123',
            passwordConfirmation: 'new-password-123'
        );

        $this->userService->updatePassword($user, $updateUserPasswordDto);

        // Password should NOT be stored as plain text
        $this->assertNotEquals('new-password-123', $user->fresh()->password);

        // Should be a bcrypt hash
        $this->assertStringStartsWith('$2y$', $user->fresh()->password);
    }
}
