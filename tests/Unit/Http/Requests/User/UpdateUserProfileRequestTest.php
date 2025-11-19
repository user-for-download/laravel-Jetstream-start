<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\User;

use App\DataTransferObjects\User\UpdateUserProfileDto;
use App\Http\Requests\User\UpdateUserProfileRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateUserProfileRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true_when_user_is_authenticated(): void
    {
        $user = User::factory()->create();

        $updateUserProfileRequest = new UpdateUserProfileRequest();
        $updateUserProfileRequest->setUserResolver(fn () => $user);

        $this->assertTrue($updateUserProfileRequest->authorize());
    }

    public function test_rules_validates_profile_fields(): void
    {
        $user = User::factory()->create();

        $updateUserProfileRequest = new UpdateUserProfileRequest();
        $updateUserProfileRequest->setUserResolver(fn () => $user);

        $rules = $updateUserProfileRequest->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('photo', $rules);
    }

    public function test_to_dto_creates_update_user_profile_dto(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $updateUserProfileRequest = new UpdateUserProfileRequest();
        $updateUserProfileRequest->setUserResolver(fn () => $user);
        $updateUserProfileRequest->replace([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $updateUserProfileRequest->files->set('photo', $file);

        $validator = \Validator::make(
            array_merge($updateUserProfileRequest->all(), ['photo' => $file]),
            [
                'name' => 'required',
                'email' => 'required|email',
                'photo' => 'nullable',
            ]
        );
        $updateUserProfileRequest->setValidator($validator);

        $updateUserProfileDto = $updateUserProfileRequest->toDto();

        $this->assertEquals('John Doe', $updateUserProfileDto->name);
        $this->assertEquals('john@example.com', $updateUserProfileDto->email);
        $this->assertInstanceOf(UploadedFile::class, $updateUserProfileDto->photo);
    }

    public function test_messages_returns_custom_validation_messages(): void
    {
        $updateUserProfileRequest = new UpdateUserProfileRequest();

        $messages = $updateUserProfileRequest->messages();

        $this->assertArrayHasKey('photo.mimes', $messages);
        $this->assertArrayHasKey('photo.max', $messages);
        $this->assertEquals(
            'The photo must be a file of type: jpg, jpeg, png.',
            $messages['photo.mimes']
        );
        $this->assertEquals(
            'The photo must not be larger than 1MB.',
            $messages['photo.max']
        );
    }

    public function test_validates_required_fields(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $updateUserProfileRequest = new UpdateUserProfileRequest();
        $updateUserProfileRequest->setUserResolver(fn () => $user);

        $validator = Validator::make([], $updateUserProfileRequest->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_allows_same_email_for_user(): void
    {
        $user = User::factory()->create(['email' => 'john@example.com']);

        $updateUserProfileRequest = new UpdateUserProfileRequest();
        $updateUserProfileRequest->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'name' => 'John Doe',
            'email' => 'john@example.com', // Same email
        ], $updateUserProfileRequest->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rejects_duplicate_email_for_different_user(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);
        $user = User::factory()->create(['email' => 'john@example.com']);

        $updateUserProfileRequest = new UpdateUserProfileRequest();
        $updateUserProfileRequest->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'name' => 'John Doe',
            'email' => 'existing@example.com',
        ], $updateUserProfileRequest->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_converts_to_dto(): void
    {
        $user = User::factory()->create();

        $updateUserProfileRequest = new UpdateUserProfileRequest();
        $updateUserProfileRequest->setUserResolver(fn () => $user);
        $updateUserProfileRequest->merge([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $updateUserProfileRequest->setValidator(
            Validator::make($updateUserProfileRequest->all(), $updateUserProfileRequest->rules())
        );

        $updateUserProfileDto = $updateUserProfileRequest->toDto();

        $this->assertInstanceOf(UpdateUserProfileDto::class, $updateUserProfileDto);
        $this->assertEquals('John Doe', $updateUserProfileDto->name);
        $this->assertEquals('john@example.com', $updateUserProfileDto->email);
    }

    public function test_authorizes_authenticated_user(): void
    {
        $user = User::factory()->create();

        $updateUserProfileRequest = new UpdateUserProfileRequest();
        $updateUserProfileRequest->setUserResolver(fn () => $user);

        $this->assertTrue($updateUserProfileRequest->authorize());
    }

    public function test_rejects_unauthenticated_user(): void
    {
        $updateUserProfileRequest = new UpdateUserProfileRequest();
        $updateUserProfileRequest->setUserResolver(fn (): null => null);

        $this->assertFalse($updateUserProfileRequest->authorize());
    }
}
