<?php

declare(strict_types=1);

namespace App\DataTransferObjects\User;

final readonly class CreateUserDto
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $passwordConfirmation = null,
        public ?bool $termsAccepted = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            passwordConfirmation: $data['password_confirmation'] ?? null,
            termsAccepted: isset($data['terms']) ? (bool) $data['terms'] : null,
        );
    }

    public function toUserAttributes(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ];
    }

    /**
     * Added for Test Compatibility
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
            'terms' => $this->termsAccepted,
        ];
    }
}
