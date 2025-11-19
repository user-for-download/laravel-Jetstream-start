<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'personal_team' => $this->personal_team,
            'owner' => new UserResource($this->owner),
            'members' => UserResource::collection($this->whenLoaded('users')),
            'invitations' => TeamInvitationResource::collection($this->whenLoaded('teamInvitations')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
