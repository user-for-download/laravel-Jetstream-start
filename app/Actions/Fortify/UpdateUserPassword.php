<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\DataTransferObjects\User\UpdateUserPasswordDto;
use App\Models\User;
use App\Services\User\UserServiceInterface;
use App\Services\Validation\PasswordValidator;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

final readonly class UpdateUserPassword implements UpdatesUserPasswords
{
    public function __construct(
        private UserServiceInterface $userService,
        private PasswordValidator $passwordValidator
    ) {}

    public function update(User $user, array $input): void
    {
        $this->validate($input);

        $updateUserPasswordDto = UpdateUserPasswordDto::fromRequest($input);
        $this->userService->updatePassword($user, $updateUserPasswordDto);
    }

    private function validate(array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:'.config('fortify.guard')],
            'password' => $this->passwordValidator->rules(),
            'password_confirmation' => ['required', 'string'],
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validateWithBag('updatePassword');
    }
}
