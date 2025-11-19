<?php

declare(strict_types=1);

namespace Tests\Unit\DataTransferObjects;

use App\DataTransferObjects\User\UpdateUserPasswordDto;
use Tests\TestCase;

class UpdateUserPasswordDtoTest extends TestCase
{
    public function test_can_create_from_request_data(): void
    {
        $data = [
            'current_password' => 'current-pass-123',
            'password' => 'new-pass-456',
            'password_confirmation' => 'new-pass-456',
        ];

        $updateUserPasswordDto = UpdateUserPasswordDto::fromRequest($data);

        $this->assertEquals('current-pass-123', $updateUserPasswordDto->currentPassword);
        $this->assertEquals('new-pass-456', $updateUserPasswordDto->password);
        $this->assertEquals('new-pass-456', $updateUserPasswordDto->passwordConfirmation);
    }

    public function test_handles_missing_fields_gracefully(): void
    {
        $data = [];

        $updateUserPasswordDto = UpdateUserPasswordDto::fromRequest($data);

        $this->assertEquals('', $updateUserPasswordDto->currentPassword);
        $this->assertEquals('', $updateUserPasswordDto->password);
        $this->assertEquals('', $updateUserPasswordDto->passwordConfirmation);
    }

    public function test_can_convert_to_array(): void
    {
        $updateUserPasswordDto = new UpdateUserPasswordDto(
            currentPassword: 'current-pass-123',
            password: 'new-pass-456',
            passwordConfirmation: 'new-pass-456'
        );

        $array = $updateUserPasswordDto->toArray();

        $this->assertEquals([
            'current_password' => 'current-pass-123',
            'password' => 'new-pass-456',
            'password_confirmation' => 'new-pass-456',
        ], $array);
    }
}
