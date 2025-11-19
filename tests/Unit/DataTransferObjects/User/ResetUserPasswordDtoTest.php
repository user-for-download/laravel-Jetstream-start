<?php

declare(strict_types=1);

namespace Tests\Unit\DataTransferObjects\User;

use App\DataTransferObjects\User\ResetUserPasswordDto;
use Tests\TestCase;

class ResetUserPasswordDtoTest extends TestCase
{
    public function test_creates_dto_from_constructor(): void
    {
        $resetUserPasswordDto = new ResetUserPasswordDto(
            password: 'newpassword123',
            passwordConfirmation: 'newpassword123'
        );

        $this->assertSame('newpassword123', $resetUserPasswordDto->password);
        $this->assertSame('newpassword123', $resetUserPasswordDto->passwordConfirmation);
    }

    public function test_creates_dto_from_request(): void
    {
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $resetUserPasswordDto = ResetUserPasswordDto::fromRequest($data);

        $this->assertSame('secret123', $resetUserPasswordDto->password);
        $this->assertSame('secret123', $resetUserPasswordDto->passwordConfirmation);
    }

    public function test_creates_dto_from_request_with_missing_fields(): void
    {
        $data = [];

        $resetUserPasswordDto = ResetUserPasswordDto::fromRequest($data);

        $this->assertSame('', $resetUserPasswordDto->password);
        $this->assertSame('', $resetUserPasswordDto->passwordConfirmation);
    }

    public function test_converts_to_array(): void
    {
        $resetUserPasswordDto = new ResetUserPasswordDto(
            password: 'mypassword',
            passwordConfirmation: 'mypassword'
        );

        $array = $resetUserPasswordDto->toArray();

        $this->assertSame([
            'password' => 'mypassword',
            'password_confirmation' => 'mypassword',
        ], $array);
    }
}
