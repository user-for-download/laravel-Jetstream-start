<?php

declare(strict_types=1);

namespace App\DataTransferObjects\User;

final readonly class UpdateUserPasswordDto
{
    public function __construct(
        public string $currentPassword,
        public string $password,
        public string $passwordConfirmation,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            currentPassword: $data['current_password'] ?? '',
            password: $data['password'] ?? '',
            passwordConfirmation: $data['password_confirmation'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'current_password' => $this->currentPassword,
            'password' => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
        ];
    }
}
