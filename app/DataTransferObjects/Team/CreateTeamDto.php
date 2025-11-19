<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Team;

use App\Models\User;

final readonly class CreateTeamDto
{
    public function __construct(
        public string $name,
        public bool $personalTeam = false,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            personalTeam: isset($data['personal_team']) && (bool) $data['personal_team'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'personal_team' => $this->personalTeam,
        ];
    }

    public function toTeamAttributes(User $user): array
    {
        return [
            'user_id' => $user->id,
            'name' => $this->name,
            'personal_team' => $this->personalTeam,
        ];
    }
}
