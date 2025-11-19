<?php

declare(strict_types=1);

namespace App\Services\User;

use App\DataTransferObjects\User\CreateUserDto;
use App\DataTransferObjects\User\ResetUserPasswordDto;
use App\DataTransferObjects\User\UpdateUserPasswordDto;
use App\DataTransferObjects\User\UpdateUserProfileDto;
use App\Models\User;

interface UserServiceInterface
{
    /**
     * Create a new user
     */
    public function createUser(CreateUserDto $createUserDto): User;

    /**
     * Update user profile information
     */
    public function updateProfileInformation(User $user, UpdateUserProfileDto $updateUserProfileDto): void;

    /**
     * Update user password
     */
    public function updatePassword(User $user, UpdateUserPasswordDto $updateUserPasswordDto): void;

    /**
     * Reset user password (via password reset flow)
     */
    public function resetPassword(User $user, ResetUserPasswordDto $resetUserPasswordDto): void;

    /**
     * Delete user and all related data
     */
    public function deleteUser(User $user): void;

    /**
     * Manually verify a user's email
     */
    public function verifyEmail(User $user): void;

    /**
     * Update user's profile photo
     */
    public function updateProfilePhoto(User $user, string $path): void;

    /**
     * Delete user's profile photo
     */
    public function deleteProfilePhoto(User $user): void;

    /**
     * Enable two-factor authentication
     */
    public function enableTwoFactorAuthentication(User $user, string $secret, array $recoveryCodes): void;

    /**
     * Disable two-factor authentication
     */
    public function disableTwoFactorAuthentication(User $user): void;

    /**
     * Replace two-factor recovery codes
     */
    public function replaceTwoFactorRecoveryCodes(User $user, array $recoveryCodes): void;
}
