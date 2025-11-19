<?php

declare(strict_types=1);

namespace Tests\Unit\DataTransferObjects\Team;

use App\DataTransferObjects\Team\AddTeamMemberDto;
use Tests\TestCase;

class AddTeamMemberDtoTest extends TestCase
{
    public function test_creates_dto_from_constructor(): void
    {
        $addTeamMemberDto = new AddTeamMemberDto(
            email: 'test@example.com',
            role: 'editor'
        );

        $this->assertSame('test@example.com', $addTeamMemberDto->email);
        $this->assertSame('editor', $addTeamMemberDto->role);
    }

    public function test_creates_dto_with_null_role(): void
    {
        $addTeamMemberDto = new AddTeamMemberDto(
            email: 'test@example.com'
        );

        $this->assertSame('test@example.com', $addTeamMemberDto->email);
        $this->assertNull($addTeamMemberDto->role);
    }

    public function test_creates_dto_from_request_with_role(): void
    {
        $data = [
            'email' => 'test@example.com',
            'role' => 'editor',
        ];

        $addTeamMemberDto = AddTeamMemberDto::fromRequest($data);

        $this->assertSame('test@example.com', $addTeamMemberDto->email);
        $this->assertSame('editor', $addTeamMemberDto->role);
    }

    public function test_creates_dto_from_request_without_role(): void
    {
        $data = [
            'email' => 'test@example.com',
        ];

        $addTeamMemberDto = AddTeamMemberDto::fromRequest($data);

        $this->assertSame('test@example.com', $addTeamMemberDto->email);
        $this->assertNull($addTeamMemberDto->role);
    }

    public function test_converts_to_array_with_role(): void
    {
        $addTeamMemberDto = new AddTeamMemberDto(
            email: 'test@example.com',
            role: 'editor'
        );

        $array = $addTeamMemberDto->toArray();

        $this->assertSame([
            'email' => 'test@example.com',
            'role' => 'editor',
        ], $array);
    }

    public function test_converts_to_array_with_null_role(): void
    {
        $addTeamMemberDto = new AddTeamMemberDto(
            email: 'test@example.com'
        );

        $array = $addTeamMemberDto->toArray();

        $this->assertSame([
            'email' => 'test@example.com',
            'role' => null,
        ], $array);
    }
}
