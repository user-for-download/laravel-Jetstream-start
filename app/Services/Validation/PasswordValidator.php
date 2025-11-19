<?php

declare(strict_types=1);

namespace App\Services\Validation;

use Illuminate\Validation\Rules\Password;

class PasswordValidator
{
    /**
     * Get the validation rules for password fields.
     */
    public function rules(): array
    {
        return ['required', 'string', Password::default(), 'confirmed'];
    }

    /**
     * Get password validation rules without confirmation.
     */
    public function rulesWithoutConfirmation(): array
    {
        return ['required', 'string', Password::default()];
    }

    /**
     * Get the default password rule.
     */
    public function passwordRule(): Password
    {
        return Password::default();
    }
}
