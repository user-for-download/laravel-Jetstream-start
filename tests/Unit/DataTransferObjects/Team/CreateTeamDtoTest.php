<?php

declare(strict_types=1);

namespace Tests\Unit\DataTransferObjects\Team;

use App\DataTransferObjects\Team\CreateTeamDto;
use Tests\TestCase;

class CreateTeamDtoTest extends TestCase
{
    public function test_creates_from_request(): void
    {
        $request = ['name' => 'My Team'];

        $createTeamDto = CreateTeamDto::fromRequest($request);

        $this->assertEquals('My Team', $createTeamDto->name);
        $this->assertFalse($createTeamDto->personalTeam);
    }

    public function test_creates_with_personal_team_flag(): void
    {
        $request = [
            'name' => 'Personal Team',
            'personal_team' => true,
        ];

        $createTeamDto = CreateTeamDto::fromRequest($request);

        $this->assertEquals('Personal Team', $createTeamDto->name);
        $this->assertTrue($createTeamDto->personalTeam);
    }

    public function test_converts_to_array(): void
    {
        $createTeamDto = new CreateTeamDto(name: 'My Team', personalTeam: false);

        $array = $createTeamDto->toArray();

        $this->assertEquals('My Team', $array['name']);
        $this->assertFalse($array['personal_team']);
    }
}
