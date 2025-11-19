<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Fortify;

use App\Actions\Fortify\CreateNewUser;
use App\DataTransferObjects\User\CreateUserDto;
use App\Models\User;
use App\Services\Team\TeamServiceInterface;
use App\Services\User\UserServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class CreateNewUserTest extends TestCase
{
    use RefreshDatabase;

    private CreateNewUser $createNewUser;

    private UserServiceInterface|(Mockery\MockInterface&Mockery\LegacyMockInterface) $userServiceMock;

    private TeamServiceInterface|(Mockery\MockInterface&Mockery\LegacyMockInterface) $teamServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userServiceMock = Mockery::mock(UserServiceInterface::class);
        $this->teamServiceMock = Mockery::mock(TeamServiceInterface::class);

        $this->createNewUser = new CreateNewUser(
            $this->userServiceMock,
            $this->teamServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_creates_user_and_personal_team_in_transaction(): void
    {
        // Arrange
        $input = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $user = User::factory()->make([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Expect transaction to be called and execute the closure
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) =>
                // Execute the closure to simulate transaction
                $callback());

        // Expect user service to be called
        $this->userServiceMock
            ->shouldReceive('createUser')
            ->once()
            ->with(Mockery::on(fn ($arg): bool => $arg instanceof CreateUserDto &&
                $arg->name === 'John Doe' &&
                $arg->email === 'john@example.com'))
            ->andReturn($user);

        // Expect team service to be called
        $this->teamServiceMock
            ->shouldReceive('createPersonalTeam')
            ->once()
            ->with(Mockery::on(fn ($arg): bool => $arg instanceof User &&
                $arg->email === $user->email));

        // Act
        $result = $this->createNewUser->create($input);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('john@example.com', $result->email);
    }

    public function test_returns_created_user(): void
    {
        // Arrange
        $input = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $user = User::factory()->make([
            'id' => 2,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn ($cb) => $cb());

        $this->userServiceMock
            ->shouldReceive('createUser')
            ->once()
            ->andReturn($user);

        $this->teamServiceMock
            ->shouldReceive('createPersonalTeam')
            ->once()
            ->with($user);

        // Act
        $result = $this->createNewUser->create($input);

        // Assert
        $this->assertSame($user, $result);
    }

    public function test_rolls_back_transaction_on_team_service_failure(): void
    {
        // Arrange
        $input = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $user = User::factory()->make(['id' => 1]);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $this->userServiceMock
            ->shouldReceive('createUser')
            ->once()
            ->andReturn($user);

        $this->teamServiceMock
            ->shouldReceive('createPersonalTeam')
            ->once()
            ->with($user)
            ->andThrow(new \Exception('Team creation failed'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Team creation failed');

        $this->createNewUser->create($input);
    }

    public function test_dto_is_created_with_correct_data(): void
    {
        // Arrange
        $input = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => true,
        ];

        $user = User::factory()->make(['id' => 1]);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        // Capture the DTO passed to user service
        $capturedDto = null;
        $this->userServiceMock
            ->shouldReceive('createUser')
            ->once()
            ->with(Mockery::capture($capturedDto))
            ->andReturn($user);

        $this->teamServiceMock
            ->shouldReceive('createPersonalTeam')
            ->once();

        // Act
        $this->createNewUser->create($input);

        // Assert
        $this->assertInstanceOf(CreateUserDto::class, $capturedDto);
        $this->assertEquals('Test User', $capturedDto->name);
        $this->assertEquals('test@example.com', $capturedDto->email);
        $this->assertEquals('SecurePass123!', $capturedDto->password);
        $this->assertEquals('SecurePass123!', $capturedDto->passwordConfirmation);
        $this->assertTrue($capturedDto->termsAccepted);
    }
}
