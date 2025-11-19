<?php

declare(strict_types=1);

namespace App\DataTransferObjects\User;

final readonly class ResetUserPasswordDto
{
    public function __construct(
        public string $password,
        public string $passwordConfirmation,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            password: $data['password'] ?? '',
            passwordConfirmation: $data['password_confirmation'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'password' => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
        ];
    }
}
