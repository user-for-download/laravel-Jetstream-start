<?php

declare(strict_types=1);

namespace App\Services\Health;

use Illuminate\Contracts\Support\Arrayable;

final readonly class HealthResult implements Arrayable
{
    public function __construct(
        public string $name,
        public string $status = 'healthy',
        public string $message = 'OK',
        public array $meta = []
    ) {}

    public static function healthy(string $name, array $meta = []): self
    {
        return new self($name, 'healthy', 'OK', $meta);
    }

    public static function degraded(string $name, string $message, array $meta = []): self
    {
        return new self($name, 'degraded', $message, $meta);
    }

    public static function unhealthy(string $name, string $error): self
    {
        return new self($name, 'unhealthy', $error);
    }

    public function toArray(): array
    {
        return array_filter([
            'status' => $this->status,
            'message' => $this->message === 'OK' ? null : $this->message,
            'meta' => $this->meta,
        ]);
    }
}
