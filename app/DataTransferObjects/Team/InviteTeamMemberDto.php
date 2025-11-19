<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Team;

final readonly class InviteTeamMemberDto
{
    public function __construct(
        public string $email,
        public ?string $role = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            email: $data['email'],
            role: $data['role'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'role' => $this->role,
        ];
    }
}
