<?php

declare(strict_types=1);

namespace Tests\Unit\DataTransferObjects\User;

use App\DataTransferObjects\User\CreateUserDto;
use Tests\TestCase;

class CreateUserDtoTest extends TestCase
{
    public function test_creates_from_valid_request(): void
    {
        $request = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ];

        $createUserDto = CreateUserDto::fromRequest($request);

        $this->assertEquals('John Doe', $createUserDto->name);
        $this->assertEquals('john@example.com', $createUserDto->email);
        $this->assertEquals('Password123!', $createUserDto->password);
        $this->assertEquals('Password123!', $createUserDto->passwordConfirmation);
        $this->assertTrue($createUserDto->termsAccepted);
    }

    public function test_converts_to_array(): void
    {
        $createUserDto = new CreateUserDto(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'Password123!',
            passwordConfirmation: 'Password123!',
            termsAccepted: true,
        );

        $array = $createUserDto->toArray();

        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ], $array);
    }

    public function test_handles_missing_optional_fields(): void
    {
        $request = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            // Missing optional fields
        ];

        $createUserDto = CreateUserDto::fromRequest($request);

        $this->assertEquals('Jane Doe', $createUserDto->name);
        $this->assertNull($createUserDto->passwordConfirmation);
        $this->assertNull($createUserDto->termsAccepted);
    }
}
