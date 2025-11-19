<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\DataTransferObjects\User\UpdateUserProfileDto;
use App\Models\User;
use App\Services\User\UserServiceInterface;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

final readonly class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function __construct(
        private UserServiceInterface $userService
    ) {}

    public function update(User $user, array $input): void
    {
        $updateUserProfileDto = UpdateUserProfileDto::fromRequest($input);

        $this->userService->updateProfileInformation($user, $updateUserProfileDto);
    }
}
