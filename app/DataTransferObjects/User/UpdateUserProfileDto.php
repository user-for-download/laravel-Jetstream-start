<?php

declare(strict_types=1);

namespace App\DataTransferObjects\User;

use Illuminate\Http\UploadedFile;

final readonly class UpdateUserProfileDto
{
    public function __construct(
        public string $name,
        public string $email,
        public ?UploadedFile $photo = null,
    ) {}

    /**
     * Create DTO from request data with explicit mapping
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            photo: $data['photo'] ?? null,
        );
    }

    /**
     * Convert DTO to array for model creation
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'photo' => $this->photo,
        ];
    }

    public function hasPhoto(): bool
    {
        return $this->photo instanceof \Illuminate\Http\UploadedFile;
    }
}
