<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\User;

use App\Http\Requests\User\UpdateUserPasswordRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateUserPasswordRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true_when_user_is_authenticated(): void
    {
        $user = User::factory()->create();

        $updateUserPasswordRequest = new UpdateUserPasswordRequest();
        $updateUserPasswordRequest->setUserResolver(fn () => $user);

        $this->assertTrue($updateUserPasswordRequest->authorize());
    }

    public function test_authorize_returns_false_when_user_is_not_authenticated(): void
    {
        $updateUserPasswordRequest = new UpdateUserPasswordRequest();
        $updateUserPasswordRequest->setUserResolver(fn (): null => null);

        $this->assertFalse($updateUserPasswordRequest->authorize());
    }

    public function test_rules_validates_password_fields(): void
    {
        $updateUserPasswordRequest = new UpdateUserPasswordRequest();

        $rules = $updateUserPasswordRequest->rules();

        $this->assertArrayHasKey('current_password', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertArrayHasKey('password_confirmation', $rules);
    }

    public function test_to_dto_creates_update_user_password_dto(): void
    {
        $updateUserPasswordRequest = new UpdateUserPasswordRequest();
        $updateUserPasswordRequest->replace([
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $validator = \Validator::make(
            $updateUserPasswordRequest->all(),
            [
                'current_password' => 'required',
                'password' => 'required',
                'password_confirmation' => 'required',
            ]
        );
        $updateUserPasswordRequest->setValidator($validator);

        $updateUserPasswordDto = $updateUserPasswordRequest->toDto();

        $this->assertEquals('OldPassword123!', $updateUserPasswordDto->currentPassword);
        $this->assertEquals('NewPassword123!', $updateUserPasswordDto->password);
        $this->assertEquals('NewPassword123!', $updateUserPasswordDto->passwordConfirmation);
    }

    public function test_messages_returns_custom_validation_messages(): void
    {
        $updateUserPasswordRequest = new UpdateUserPasswordRequest();

        $messages = $updateUserPasswordRequest->messages();

        $this->assertArrayHasKey('current_password.current_password', $messages);
        $this->assertEquals(
            'The provided password does not match your current password.',
            $messages['current_password.current_password']
        );
    }

    public function test_error_bag_returns_update_password(): void
    {
        $updateUserPasswordRequest = new UpdateUserPasswordRequest();

        $reflectionClass = new \ReflectionClass($updateUserPasswordRequest);
        $reflectionMethod = $reflectionClass->getMethod('errorBag');

        $errorBag = $reflectionMethod->invoke($updateUserPasswordRequest);

        $this->assertEquals('updatePassword', $errorBag);
    }
}
