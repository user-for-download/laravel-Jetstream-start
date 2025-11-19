<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\DataTransferObjects\User\CreateUserDto;
use App\Models\User;
use App\Services\Team\TeamServiceInterface;
use App\Services\User\UserServiceInterface;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final readonly class CreateNewUser implements CreatesNewUsers
{
    public function __construct(
        private UserServiceInterface $userService,
        private TeamServiceInterface $teamService
    ) {}

    public function create(array $input): User
    {
        $createUserDto = CreateUserDto::fromRequest($input);

        return DB::transaction(function () use ($createUserDto): User {
            $user = $this->userService->createUser($createUserDto);
            $this->teamService->createPersonalTeam($user);

            return $user;
        });
    }
}
