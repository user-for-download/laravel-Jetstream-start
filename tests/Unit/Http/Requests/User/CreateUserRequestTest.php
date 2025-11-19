<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\User;

use App\DataTransferObjects\User\CreateUserDto;
use App\Http\Requests\User\CreateUserRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CreateUserRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validates_required_fields(): void
    {
        $createUserRequest = new CreateUserRequest();

        $validator = Validator::make([], $createUserRequest->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_validates_email_format(): void
    {
        $createUserRequest = new CreateUserRequest();

        $validator = Validator::make([
            'email' => 'invalid-email',
        ], $createUserRequest->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_validates_unique_email(): void
    {
        \App\Models\User::factory()->create(['email' => 'existing@example.com']);

        $createUserRequest = new CreateUserRequest();

        $validator = Validator::make([
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ], $createUserRequest->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_converts_to_dto(): void
    {
        $createUserRequest = new CreateUserRequest();

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            // Corrected the key from camelCase to snake_case
            'password_confirmation' => 'Password123!',
        ];

        // Set the request data
        $createUserRequest->merge($data);

        // The rest of your test setup is correct
        $validator = Validator::make($data, $createUserRequest->rules());
        $createUserRequest->setValidator($validator);

        // Call the method under test
        $createUserDto = $createUserRequest->toDto();

        // Assertions
        $this->assertInstanceOf(CreateUserDto::class, $createUserDto);
        $this->assertEquals('John Doe', $createUserDto->name);
        $this->assertEquals('john@example.com', $createUserDto->email);
        $this->assertEquals('Password123!', $createUserDto->password);
        $this->assertEquals('Password123!', $createUserDto->passwordConfirmation);
    }

    public function test_has_custom_messages(): void
    {
        $createUserRequest = new CreateUserRequest();
        $messages = $createUserRequest->messages();

        $this->assertArrayHasKey('email.unique', $messages);
        $this->assertArrayHasKey('terms.accepted', $messages);
    }

    public function test_has_custom_attributes(): void
    {
        $createUserRequest = new CreateUserRequest();
        $attributes = $createUserRequest->attributes();

        $this->assertArrayHasKey('name', $attributes);
        $this->assertEquals('full name', $attributes['name']);
    }
}
