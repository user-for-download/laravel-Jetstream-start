<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Team;

final readonly class UpdateTeamNameDto
{
    public function __construct(
        public string $name,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
