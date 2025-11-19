<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTeamRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeamRoles;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The "booted" method of the model.
     * Refactoring: Automated cleanup of related data upon deletion.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            // 1. Detach from all teams
            $user->teams()->detach();

            // 2. Delete owned teams (Model cascading)
            $user->ownedTeams->each->delete();

            // 3. Delete profile photo from storage
            $user->deleteProfilePhoto();

            // 4. Delete API tokens
            $user->tokens->each->delete();
        });
    }
}
