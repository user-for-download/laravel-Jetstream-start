<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\DataTransferObjects\Team\AddTeamMemberDto;
use App\DataTransferObjects\Team\CreateTeamDto;
use App\DataTransferObjects\Team\InviteTeamMemberDto;
use App\DataTransferObjects\Team\UpdateTeamNameDto;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

interface TeamServiceInterface
{
    /**
     * Create a new team
     */
    public function createTeam(User $user, CreateTeamDto $createTeamDto): Team;

    /**
     * Update team name
     */
    public function updateTeamName(Team $team, UpdateTeamNameDto $updateTeamNameDto): void;

    /**
     * Add an existing user to a team
     */
    public function addTeamMember(Team $team, AddTeamMemberDto $addTeamMemberDto): void;

    /**
     * Invite a user to join a team via email
     */
    public function inviteTeamMember(Team $team, InviteTeamMemberDto $inviteTeamMemberDto): TeamInvitation;

    /**
     * Remove a member from a team
     */
    public function removeTeamMember(Team $team, User $user): void;

    /**
     * Update a team member's role
     */
    public function updateTeamMemberRole(Team $team, User $user, string $role): void;

    /**
     * Delete a team invitation
     */
    public function deleteTeamInvitation(Team $team, string $email): void;

    /**
     * Delete a team
     */
    public function deleteTeam(Team $team): void;

    /**
     * Create a personal team for a user
     */
    public function createPersonalTeam(User $user): Team;

    /**
     * Switch user's current team
     */
    public function switchTeam(User $user, Team $team): void;

    public function transferOwnership(Team $team, User $user): void;
}
