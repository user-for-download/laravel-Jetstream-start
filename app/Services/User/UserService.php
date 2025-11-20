<?php

declare(strict_types=1);

namespace App\Services\User;

use App\DataTransferObjects\User\CreateUserDto;
use App\DataTransferObjects\User\ResetUserPasswordDto;
use App\DataTransferObjects\User\UpdateUserPasswordDto;
use App\DataTransferObjects\User\UpdateUserProfileDto;
use App\Models\User;
use App\Services\Concerns\ExecutesInTransaction;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Hash;

readonly class UserService implements UserServiceInterface
{
    use ExecutesInTransaction;

    public function createUser(CreateUserDto $createUserDto): User
    {
        // Logic moved: Hash the password here, not in the DTO
        $attributes = $createUserDto->toUserAttributes();
        $attributes['password'] = Hash::make($attributes['password']);

        return $this->transaction(fn (): User => User::create($attributes));
    }

    public function deleteUser(User $user): void
    {
        $this->transaction(fn () => $user->delete());
    }

    public function updateProfileInformation(User $user, UpdateUserProfileDto $updateUserProfileDto): void
    {
        $this->transaction(function () use ($user, $updateUserProfileDto): void {
            if ($updateUserProfileDto->hasPhoto()) {
                $user->updateProfilePhoto($updateUserProfileDto->photo);
            }

            if ($this->shouldVerifyEmail($user, $updateUserProfileDto)) {
                $this->updateUserAndRequireEmailVerification($user, $updateUserProfileDto);

                return;
            }

            $user->update($updateUserProfileDto->toArray());
        });
    }

    public function updatePassword(User $user, UpdateUserPasswordDto $updateUserPasswordDto): void
    {
        $user->forceFill([
            'password' => Hash::make($updateUserPasswordDto->password),
        ])->save();
    }

    public function resetPassword(User $user, ResetUserPasswordDto $resetUserPasswordDto): void
    {
        $user->forceFill([
            'password' => Hash::make($resetUserPasswordDto->password),
        ])->save();
    }

    public function verifyEmail(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->forceFill(['email_verified_at' => now()])->save();
    }

    public function updateProfilePhoto(User $user, string $path): void
    {
        if ($user->profile_photo_path) {
            $user->deleteProfilePhoto();
        }

        $user->forceFill(['profile_photo_path' => $path])->save();
    }

    public function deleteProfilePhoto(User $user): void
    {
        $user->deleteProfilePhoto();
        $user->forceFill(['profile_photo_path' => null])->save();
    }

    public function enableTwoFactorAuthentication(User $user, string $secret, array $recoveryCodes): void
    {
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => now(),
        ])->save();
    }

    public function disableTwoFactorAuthentication(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    public function replaceTwoFactorRecoveryCodes(User $user, array $recoveryCodes): void
    {
        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ])->save();
    }

    // --- Private Helpers ---

    private function shouldVerifyEmail(User $user, UpdateUserProfileDto $updateUserProfileDto): bool
    {
        return $updateUserProfileDto->email !== $user->email && $user instanceof MustVerifyEmail;
    }

    private function updateUserAndRequireEmailVerification(User $user, UpdateUserProfileDto $updateUserProfileDto): void
    {
        $user->forceFill([
            'name' => $updateUserProfileDto->name,
            'email' => $updateUserProfileDto->email,
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }

    /**
     * Preloads all necessary relationships for the dashboard.
     */
    public function loadDashboardData(User $user): void
    {
        $user->load([
            'teams',
            'ownedTeams',
            'currentTeam.users', // Load members
            'currentTeam.owner', // Load owner details
            'currentTeam.teamInvitations', // Load pending invites
        ]);
    }
}
