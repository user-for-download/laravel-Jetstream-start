<?php

declare(strict_types=1);

namespace Tests\Unit\Services\User;

use App\DataTransferObjects\User\CreateUserDto;
use App\DataTransferObjects\User\ResetUserPasswordDto;
use App\DataTransferObjects\User\UpdateUserPasswordDto;
use App\DataTransferObjects\User\UpdateUserProfileDto;
use App\Models\Team;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Features;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = app(UserService::class);
    }

    public function test_clears_email_verification_when_email_changes(): void
    {
        if (!Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        Notification::fake();

        $user = User::factory()->create([
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $updateUserProfileDto = new UpdateUserProfileDto(
            name: $user->name,
            email: 'new@example.com'
        );

        $this->userService->updateProfileInformation($user, $updateUserProfileDto);

        $user->refresh();

        $this->assertEquals('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_creates_user_successfully(): void
    {
        $createUserDto = new CreateUserDto(
            name: 'John Doe',
            email: 'john@test.com',
            password: 'password123',
            passwordConfirmation: 'password123'
        );

        $user = $this->userService->createUser($createUserDto);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@test.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_updates_profile_information(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@test.com',
        ]);

        $updateUserProfileDto = new UpdateUserProfileDto(
            name: 'New Name',
            email: 'new@test.com'
        );

        $this->userService->updateProfileInformation($user, $updateUserProfileDto);

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('new@test.com', $user->email);
    }

    public function test_updates_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $updateUserProfileDto = new UpdateUserProfileDto(
            name: $user->name,
            email: $user->email,
            photo: $file
        );

        $this->userService->updateProfileInformation($user, $updateUserProfileDto);

        $this->assertNotNull($user->fresh()->profile_photo_path);
    }

    public function test_updates_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $updateUserPasswordDto = new UpdateUserPasswordDto(
            currentPassword: 'oldpassword',
            password: 'newpassword',
            passwordConfirmation: 'newpassword'
        );

        $this->userService->updatePassword($user, $updateUserPasswordDto);

        $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
    }

    public function test_resets_password(): void
    {
        $user = User::factory()->create();

        $resetUserPasswordDto = new ResetUserPasswordDto(
            password: 'resetpassword',
            passwordConfirmation: 'resetpassword'
        );

        $this->userService->resetPassword($user, $resetUserPasswordDto);

        $this->assertTrue(Hash::check('resetpassword', $user->fresh()->password));
    }

    public function test_deletes_user_and_related_data(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);
        $user->createToken('test-token');

        $userId = $user->id;
        $teamId = $team->id;

        $this->userService->deleteUser($user);

        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertDatabaseMissing('teams', ['id' => $teamId]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $userId]);
    }

    public function test_verifies_user_email(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->userService->verifyEmail($user);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_deletes_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('avatar.jpg')->store('profile-photos', 'public');
        $user->forceFill(['profile_photo_path' => $path])->save();

        $this->userService->deleteProfilePhoto($user);

        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_enables_two_factor_authentication(): void
    {
        $user = User::factory()->create();

        $this->userService->enableTwoFactorAuthentication(
            $user,
            'secret-key',
            ['code1', 'code2', 'code3']
        );

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNotNull($user->two_factor_recovery_codes);
        $this->assertNotNull($user->two_factor_confirmed_at);
    }

    public function test_disables_two_factor_authentication(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1'])),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->userService->disableTwoFactorAuthentication($user);

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }
}
