<?php

declare(strict_types=1);

namespace Tests\Unit\DataTransferObjects\Team;

use App\DataTransferObjects\Team\UpdateTeamNameDto;
use Tests\TestCase;

class UpdateTeamNameDtoTest extends TestCase
{
    public function test_creates_dto_from_constructor(): void
    {
        $updateTeamNameDto = new UpdateTeamNameDto(name: 'New Team Name');

        $this->assertSame('New Team Name', $updateTeamNameDto->name);
    }

    public function test_creates_dto_from_request(): void
    {
        $data = ['name' => 'Updated Team'];

        $updateTeamNameDto = UpdateTeamNameDto::fromRequest($data);

        $this->assertSame('Updated Team', $updateTeamNameDto->name);
    }

    public function test_converts_to_array(): void
    {
        $updateTeamNameDto = new UpdateTeamNameDto(name: 'My Team');

        $array = $updateTeamNameDto->toArray();

        $this->assertSame(['name' => 'My Team'], $array);
    }
}
