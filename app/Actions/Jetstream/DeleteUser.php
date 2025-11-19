<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Models\User;
use App\Services\User\UserServiceInterface;
use Laravel\Jetstream\Contracts\DeletesUsers;

final readonly class DeleteUser implements DeletesUsers
{
    public function __construct(
        private UserServiceInterface $userService
    ) {}

    public function delete(User $user): void
    {
        $this->userService->deleteUser($user);
    }
}
