<?php

declare(strict_types=1);

namespace Tests\Unit\DataTransferObjects\User;

use App\DataTransferObjects\User\UpdateUserProfileDto;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UpdateUserProfileDtoTest extends TestCase
{
    public function test_creates_dto_from_constructor(): void
    {
        $updateUserProfileDto = new UpdateUserProfileDto(
            name: 'John Doe',
            email: 'john@example.com'
        );

        $this->assertSame('John Doe', $updateUserProfileDto->name);
        $this->assertSame('john@example.com', $updateUserProfileDto->email);
        $this->assertNull($updateUserProfileDto->photo);
    }

    public function test_creates_dto_with_photo(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $updateUserProfileDto = new UpdateUserProfileDto(
            name: 'John Doe',
            email: 'john@example.com',
            photo: $file
        );

        $this->assertSame('John Doe', $updateUserProfileDto->name);
        $this->assertSame('john@example.com', $updateUserProfileDto->email);
        $this->assertInstanceOf(UploadedFile::class, $updateUserProfileDto->photo);
    }

    public function test_creates_dto_from_request(): void
    {
        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ];

        $updateUserProfileDto = UpdateUserProfileDto::fromRequest($data);

        $this->assertSame('Jane Doe', $updateUserProfileDto->name);
        $this->assertSame('jane@example.com', $updateUserProfileDto->email);
        $this->assertNull($updateUserProfileDto->photo);
    }

    public function test_creates_dto_from_request_with_photo(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'photo' => $file,
        ];

        $updateUserProfileDto = UpdateUserProfileDto::fromRequest($data);

        $this->assertSame('Jane Doe', $updateUserProfileDto->name);
        $this->assertSame('jane@example.com', $updateUserProfileDto->email);
        $this->assertInstanceOf(UploadedFile::class, $updateUserProfileDto->photo);
    }

    public function test_converts_to_array_without_photo(): void
    {
        $updateUserProfileDto = new UpdateUserProfileDto(
            name: 'John Doe',
            email: 'john@example.com'
        );

        $array = $updateUserProfileDto->toArray();

        $this->assertSame([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'photo' => null,
        ], $array);
    }

    public function test_converts_to_array_with_photo(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $updateUserProfileDto = new UpdateUserProfileDto(
            name: 'John Doe',
            email: 'john@example.com',
            photo: $file
        );

        $array = $updateUserProfileDto->toArray();

        $this->assertSame('John Doe', $array['name']);
        $this->assertSame('john@example.com', $array['email']);
        $this->assertInstanceOf(UploadedFile::class, $array['photo']);
    }

    public function test_has_photo_returns_false_when_no_photo(): void
    {
        $updateUserProfileDto = new UpdateUserProfileDto(
            name: 'John Doe',
            email: 'john@example.com'
        );

        $this->assertFalse($updateUserProfileDto->hasPhoto());
    }

    public function test_has_photo_returns_true_when_photo_present(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $updateUserProfileDto = new UpdateUserProfileDto(
            name: 'John Doe',
            email: 'john@example.com',
            photo: $file
        );

        $this->assertTrue($updateUserProfileDto->hasPhoto());
    }
}
