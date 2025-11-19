<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Team;

use App\DataTransferObjects\Team\CreateTeamDto;
use App\Http\Requests\Team\CreateTeamRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CreateTeamRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validates_required_fields(): void
    {
        $createTeamRequest = new CreateTeamRequest();

        $validator = Validator::make([], $createTeamRequest->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validates_max_length(): void
    {
        $createTeamRequest = new CreateTeamRequest();

        $validator = Validator::make([
            'name' => str_repeat('a', 256), // 256 characters
        ], $createTeamRequest->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_passes_with_valid_data(): void
    {
        $createTeamRequest = new CreateTeamRequest();

        $validator = Validator::make([
            'name' => 'My Team',
        ], $createTeamRequest->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_converts_to_dto(): void
    {
        $createTeamRequest = new CreateTeamRequest();
        $createTeamRequest->merge(['name' => 'My Team']);

        $createTeamRequest->setValidator(
            Validator::make($createTeamRequest->all(), $createTeamRequest->rules())
        );

        $createTeamDto = $createTeamRequest->toDto();

        $this->assertInstanceOf(CreateTeamDto::class, $createTeamDto);
        $this->assertEquals('My Team', $createTeamDto->name);
    }

    public function test_authorizes_authenticated_user(): void
    {
        $user = User::factory()->create();

        $createTeamRequest = new CreateTeamRequest();
        $createTeamRequest->setUserResolver(fn () => $user);

        $this->assertTrue($createTeamRequest->authorize());
    }

    public function test_has_custom_messages(): void
    {
        $createTeamRequest = new CreateTeamRequest();
        $messages = $createTeamRequest->messages();

        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('name.max', $messages);
    }
}
