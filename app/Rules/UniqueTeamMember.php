<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Team;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueTeamMember implements ValidationRule
{
    public function __construct(
        private readonly Team $team
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->team->hasUserWithEmail($value)) {
            $fail('This user already belongs to the team.');
        }
    }
}
