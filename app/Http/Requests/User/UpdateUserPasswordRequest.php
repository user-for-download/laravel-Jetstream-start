<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\DataTransferObjects\User\UpdateUserPasswordDto;
use App\Http\Requests\BaseFormRequest;
use App\Services\Validation\PasswordValidator;

final class UpdateUserPasswordRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $passwordValidator = app(PasswordValidator::class);

        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $passwordValidator->rules(),
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function toDto(): UpdateUserPasswordDto
    {
        return UpdateUserPasswordDto::fromRequest($this->validated());
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'The provided password does not match your current password.',
        ];
    }

    protected function errorBag(): string
    {
        return 'updatePassword';
    }
}
