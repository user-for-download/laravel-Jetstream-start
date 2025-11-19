<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\DataTransferObjects\User\ResetUserPasswordDto;
use App\Models\User;
use App\Services\User\UserServiceInterface;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

final readonly class ResetUserPassword implements ResetsUserPasswords
{
    public function __construct(
        private UserServiceInterface $userService
    ) {}

    public function reset(User $user, array $input): void
    {
        $resetUserPasswordDto = ResetUserPasswordDto::fromRequest($input);

        $this->userService->resetPassword($user, $resetUserPasswordDto);
    }
}
