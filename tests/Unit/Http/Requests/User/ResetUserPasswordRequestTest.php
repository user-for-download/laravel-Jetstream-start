<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\User;

use App\Http\Requests\User\ResetUserPasswordRequest;
use Tests\TestCase;

class ResetUserPasswordRequestTest extends TestCase
{
    public function test_rules_validates_password_fields(): void
    {
        $resetUserPasswordRequest = new ResetUserPasswordRequest();

        $rules = $resetUserPasswordRequest->rules();

        $this->assertArrayHasKey('password', $rules);
        $this->assertArrayHasKey('password_confirmation', $rules);
    }

    public function test_to_dto_creates_reset_user_password_dto(): void
    {
        $resetUserPasswordRequest = new ResetUserPasswordRequest();
        $resetUserPasswordRequest->replace([
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $validator = \Validator::make(
            $resetUserPasswordRequest->all(),
            [
                'password' => 'required',
                'password_confirmation' => 'required',
            ]
        );
        $resetUserPasswordRequest->setValidator($validator);

        $resetUserPasswordDto = $resetUserPasswordRequest->toDto();

        $this->assertEquals('NewPassword123!', $resetUserPasswordDto->password);
        $this->assertEquals('NewPassword123!', $resetUserPasswordDto->passwordConfirmation);
    }
}
