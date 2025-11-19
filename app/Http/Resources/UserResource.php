<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->whenPivotLoaded('team_user', fn () => $this->pivot->role),
            'profile_photo_url' => $this->profile_photo_url,
            'joined_at' => $this->whenPivotLoaded('team_user', fn () => $this->pivot->created_at?->toIso8601String()),
        ];
    }
}
