<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\DataTransferObjects\User\ResetUserPasswordDto;
use App\Http\Requests\BaseFormRequest;
use App\Services\Validation\PasswordValidator;

final class ResetUserPasswordRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $passwordValidator = app(PasswordValidator::class);

        return [
            'password' => $passwordValidator->rules(),
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function toDto(): ResetUserPasswordDto
    {
        return new ResetUserPasswordDto(
            password: $this->validated('password'),
            passwordConfirmation: $this->input('password_confirmation'),
        );
    }
}
