<?php

declare(strict_types=1);

namespace Tests\Unit\DataTransferObjects\Team;

use App\DataTransferObjects\Team\InviteTeamMemberDto;
use Tests\TestCase;

class InviteTeamMemberDtoTest extends TestCase
{
    public function test_creates_dto_from_constructor(): void
    {
        $inviteTeamMemberDto = new InviteTeamMemberDto(
            email: 'invite@example.com',
            role: 'editor'
        );

        $this->assertSame('invite@example.com', $inviteTeamMemberDto->email);
        $this->assertSame('editor', $inviteTeamMemberDto->role);
    }

    public function test_creates_dto_with_null_role(): void
    {
        $inviteTeamMemberDto = new InviteTeamMemberDto(
            email: 'invite@example.com'
        );

        $this->assertSame('invite@example.com', $inviteTeamMemberDto->email);
        $this->assertNull($inviteTeamMemberDto->role);
    }

    public function test_creates_dto_from_request_with_role(): void
    {
        $data = [
            'email' => 'invite@example.com',
            'role' => 'admin',
        ];

        $inviteTeamMemberDto = InviteTeamMemberDto::fromRequest($data);

        $this->assertSame('invite@example.com', $inviteTeamMemberDto->email);
        $this->assertSame('admin', $inviteTeamMemberDto->role);
    }

    public function test_creates_dto_from_request_without_role(): void
    {
        $data = [
            'email' => 'invite@example.com',
        ];

        $inviteTeamMemberDto = InviteTeamMemberDto::fromRequest($data);

        $this->assertSame('invite@example.com', $inviteTeamMemberDto->email);
        $this->assertNull($inviteTeamMemberDto->role);
    }

    public function test_converts_to_array_with_role(): void
    {
        $inviteTeamMemberDto = new InviteTeamMemberDto(
            email: 'invite@example.com',
            role: 'editor'
        );

        $array = $inviteTeamMemberDto->toArray();

        $this->assertSame([
            'email' => 'invite@example.com',
            'role' => 'editor',
        ], $array);
    }

    public function test_converts_to_array_with_null_role(): void
    {
        $inviteTeamMemberDto = new InviteTeamMemberDto(
            email: 'invite@example.com'
        );

        $array = $inviteTeamMemberDto->toArray();

        $this->assertSame([
            'email' => 'invite@example.com',
            'role' => null,
        ], $array);
    }
}
