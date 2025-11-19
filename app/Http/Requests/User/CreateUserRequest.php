<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\DataTransferObjects\User\CreateUserDto;
use App\Http\Requests\BaseFormRequest;
use App\Services\Validation\PasswordValidator;
use Laravel\Jetstream\Jetstream;

final class CreateUserRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $passwordValidator = app(PasswordValidator::class);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $passwordValidator->rules(),
            'password_confirmation' => ['required', 'string'],
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature()
                ? ['accepted', 'required']
                : '',
        ];
    }

    public function toDto(): CreateUserDto
    {
        return CreateUserDto::fromRequest($this->validated());
    }

    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'password' => 'password',
            'terms' => 'terms and conditions',
        ];
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => 'You must accept the terms and conditions.',
            'email.unique' => 'This email address is already registered.',
        ];
    }
}
