<?php

// ========== database/seeders/DatabaseSeeder.php (исправленная версия) ==========

declare(strict_types=1);

namespace Database\Seeders;

use App\DataTransferObjects\Team\AddTeamMemberDto;
use App\DataTransferObjects\Team\CreateTeamDto;
use App\DataTransferObjects\Team\InviteTeamMemberDto;
use App\DataTransferObjects\User\CreateUserDto;
use App\Enums\RoleEnum;
use App\Models\Team;
use App\Models\User;
use App\Services\Team\TeamServiceInterface;
use App\Services\User\UserServiceInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Laravel\Jetstream\Jetstream;

class DatabaseSeeder extends Seeder
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly TeamServiceInterface $teamService
    ) {}

    public function run(): void
    {
        // Check if we already have users
        if (User::where('email', 'admin@example.com')->exists()) {
            $this->command->warn('Database appears to be already seeded. Skipping...');
            $this->displaySeedingSummary();

            return;
        }

        $this->command->info('🌱 Seeding database with role-based system...');
        $this->command->newLine();

        DB::transaction(function (): void {

            $this->command->info('👥 Creating users...');

            // Admin user
            $user = $this->createVerifiedUser(
                'Admin User',
                'admin@example.com',
                'password'
            );

            // Regular users
            $john = $this->createVerifiedUser(
                'John Doe',
                'john@example.com',
                'password'
            );

            $jane = $this->createVerifiedUser(
                'Jane Smith',
                'jane@example.com',
                'password'
            );

            $bob = $this->createVerifiedUser(
                'Bob Johnson',
                'bob@example.com',
                'password'
            );

            $sarah = $this->createVerifiedUser(
                'Sarah Williams',
                'sarah@example.com',
                'password'
            );

            $mike = $this->createVerifiedUser(
                'Mike Brown',
                'mike@example.com',
                'password'
            );

            $this->command->info('  ✓ Created 6 users');
            $this->command->newLine();

            $this->command->info('🏠 Creating personal teams...');

            $this->teamService->createPersonalTeam($user);
            $this->teamService->createPersonalTeam($john);
            $this->teamService->createPersonalTeam($jane);
            $this->teamService->createPersonalTeam($bob);
            $this->teamService->createPersonalTeam($sarah);
            $this->teamService->createPersonalTeam($mike);

            $this->command->info('  ✓ Created 6 personal teams');
            $this->command->newLine();

            $this->command->info('🏢 Creating shared teams...');

            // Admin Team - Full control team
            $adminTeam = $this->teamService->createTeam(
                $user,
                new CreateTeamDto(
                    name: 'Admin Team',
                    personalTeam: false
                )
            );
            $this->command->info('  ✓ Admin Team (Owner: admin@example.com)');

            // Project Alpha - Development team
            $projectTeam = $this->teamService->createTeam(
                $john,
                new CreateTeamDto(
                    name: 'Project Alpha',
                    personalTeam: false
                )
            );
            $this->command->info('  ✓ Project Alpha (Owner: john@example.com)');

            // Marketing Team
            $marketingTeam = $this->teamService->createTeam(
                $jane,
                new CreateTeamDto(
                    name: 'Marketing Team',
                    personalTeam: false
                )
            );
            $this->command->info('  ✓ Marketing Team (Owner: jane@example.com)');

            // Development Team
            $devTeam = $this->teamService->createTeam(
                $bob,
                new CreateTeamDto(
                    name: 'Development Team',
                    personalTeam: false
                )
            );
            $this->command->info('  ✓ Development Team (Owner: bob@example.com)');

            // Design Team
            $designTeam = $this->teamService->createTeam(
                $sarah,
                new CreateTeamDto(
                    name: 'Design Team',
                    personalTeam: false
                )
            );
            $this->command->info('  ✓ Design Team (Owner: sarah@example.com)');

            // Support Team
            $supportTeam = $this->teamService->createTeam(
                $mike,
                new CreateTeamDto(
                    name: 'Support Team',
                    personalTeam: false
                )
            );
            $this->command->info('  ✓ Support Team (Owner: mike@example.com)');

            $this->command->newLine();
            $this->command->info('👨‍💼 Adding team members with roles...');

            // Admin Team - Everyone has different roles
            $this->addMember($adminTeam->id, $john->email, RoleEnum::ADMIN->value, 'Admin Team');
            $this->addMember($adminTeam->id, $jane->email, RoleEnum::EDITOR->value, 'Admin Team');
            $this->addMember($adminTeam->id, $sarah->email, RoleEnum::VIEWER->value, 'Admin Team');
            $this->addMember($adminTeam->id, $mike->email, RoleEnum::VIEWER->value, 'Admin Team');

            // Project Alpha - Development focused
            $this->addMember($projectTeam->id, $jane->email, RoleEnum::EDITOR->value, 'Project Alpha');
            $this->addMember($projectTeam->id, $bob->email, RoleEnum::ADMIN->value, 'Project Alpha');
            $this->addMember($projectTeam->id, $sarah->email, RoleEnum::EDITOR->value, 'Project Alpha');
            $this->addMember($projectTeam->id, $user->email, RoleEnum::ADMIN->value, 'Project Alpha');

            // Marketing Team
            $this->addMember($marketingTeam->id, $user->email, RoleEnum::ADMIN->value, 'Marketing Team');
            $this->addMember($marketingTeam->id, $bob->email, RoleEnum::VIEWER->value, 'Marketing Team');
            $this->addMember($marketingTeam->id, $mike->email, RoleEnum::EDITOR->value, 'Marketing Team');

            // Development Team
            $this->addMember($devTeam->id, $john->email, RoleEnum::ADMIN->value, 'Development Team');
            $this->addMember($devTeam->id, $sarah->email, RoleEnum::EDITOR->value, 'Development Team');
            $this->addMember($devTeam->id, $user->email, RoleEnum::ADMIN->value, 'Development Team');

            // Design Team
            $this->addMember($designTeam->id, $jane->email, RoleEnum::EDITOR->value, 'Design Team');
            $this->addMember($designTeam->id, $user->email, RoleEnum::VIEWER->value, 'Design Team');
            $this->addMember($designTeam->id, $mike->email, RoleEnum::VIEWER->value, 'Design Team');

            // Support Team
            $this->addMember($supportTeam->id, $user->email, RoleEnum::ADMIN->value, 'Support Team');
            $this->addMember($supportTeam->id, $john->email, RoleEnum::EDITOR->value, 'Support Team');
            $this->addMember($supportTeam->id, $jane->email, RoleEnum::VIEWER->value, 'Support Team');

            $this->command->newLine();
            $this->command->info('✉️  Creating team invitations...');

            $this->inviteMember($adminTeam->id, 'newadmin@example.com', RoleEnum::ADMIN->value, 'Admin Team');
            $this->inviteMember($projectTeam->id, 'developer@example.com', RoleEnum::EDITOR->value, 'Project Alpha');
            $this->inviteMember($marketingTeam->id, 'marketer@example.com', RoleEnum::EDITOR->value, 'Marketing Team');
            $this->inviteMember($devTeam->id, 'newdev@example.com', RoleEnum::VIEWER->value, 'Development Team');
            $this->inviteMember($designTeam->id, 'designer@example.com', RoleEnum::EDITOR->value, 'Design Team');

            $this->command->newLine();
            $this->command->info('🎲 Creating additional demo users...');
            $this->createAdditionalUsers(5);
        });

        $this->displaySeedingSummary();
    }

    private function createVerifiedUser(string $name, string $email, string $password): User
    {
        $user = $this->userService->createUser(
            new CreateUserDto(
                name: $name,
                email: $email,
                password: $password,
                passwordConfirmation: $password,
                termsAccepted: Jetstream::hasTermsAndPrivacyPolicyFeature()
            )
        );

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        return $user->fresh();
    }

    private function addMember(int $teamId, string $email, string $role, string $teamName): void
    {
        try {
            $team = Team::findOrFail($teamId);
            $roleEnum = RoleEnum::from($role);

            $this->teamService->addTeamMember(
                $team,
                new AddTeamMemberDto(
                    email: $email,
                    role: $role
                )
            );

            $this->command->info(sprintf(
                '  ✓ Added %s to %s as %s',
                $email,
                $teamName,
                $roleEnum->getLabel()
            ));
        } catch (\Exception $exception) {
            $this->command->warn(sprintf(
                '  ✗ Failed to add %s to %s: %s',
                $email,
                $teamName,
                $exception->getMessage()
            ));
        }
    }

    private function inviteMember(int $teamId, string $email, string $role, string $teamName): void
    {
        try {
            $team = Team::findOrFail($teamId);
            $roleEnum = RoleEnum::from($role);

            $this->teamService->inviteTeamMember(
                $team,
                new InviteTeamMemberDto(
                    email: $email,
                    role: $role
                )
            );

            $this->command->info(sprintf(
                '  ✓ Invited %s to %s as %s',
                $email,
                $teamName,
                $roleEnum->getLabel()
            ));
        } catch (\Exception $exception) {
            $this->command->warn(sprintf(
                '  ✗ Failed to invite %s to %s: %s',
                $email,
                $teamName,
                $exception->getMessage()
            ));
        }
    }

    private function createAdditionalUsers(int $count): void
    {
        $users = User::factory($count)->make();

        foreach ($users as $user) {
            $createdUser = $this->createVerifiedUser(
                $user->name,
                $user->email,
                'password'
            );

            $this->teamService->createPersonalTeam($createdUser);
        }

        $this->command->info(sprintf('  ✓ Created %d additional users with personal teams', $count));
    }

    private function displaySeedingSummary(): void
    {
        $userCount = User::count();
        $teamCount = Team::count();
        $personalTeamCount = Team::where('personal_team', true)->count();
        $sharedTeamCount = Team::where('personal_team', false)->count();
        $invitationCount = 0;

        if (Jetstream::teamInvitationModel()) {
            $invitationCount = Jetstream::teamInvitationModel()::count();
        }

        $this->command->newLine(2);
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('✨ Database seeded successfully!');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->newLine();

        $this->command->info('📊 Summary:');
        $this->command->table(
            ['Metric', 'Count'],
            [
                ['Total Users', $userCount],
                ['Total Teams', $teamCount],
                ['  └─ Personal Teams', $personalTeamCount],
                ['  └─ Shared Teams', $sharedTeamCount],
                ['Pending Invitations', $invitationCount],
            ]
        );

        $this->command->newLine();
        $this->command->info('🔑 Test Accounts:');
        $this->command->line('   All passwords: password');
        $this->command->newLine();

        $this->command->table(
            ['Name', 'Email', 'Type'],
            [
                ['Admin User', 'admin@example.com', '👑 Admin'],
                ['John Doe', 'john@example.com', '👤 User'],
                ['Jane Smith', 'jane@example.com', '👤 User'],
                ['Bob Johnson', 'bob@example.com', '👤 User'],
                ['Sarah Williams', 'sarah@example.com', '👤 User'],
                ['Mike Brown', 'mike@example.com', '👤 User'],
            ]
        );

        $this->command->newLine();
        $this->command->info('🏢 Teams & Members:');
        $this->command->newLine();

        $teams = Team::where('personal_team', false)
            ->with(['owner', 'users'])
            ->get();

        foreach ($teams as $team) {
            $this->command->line(sprintf('  📁 %s', $team->name));
            $this->command->line(sprintf('     Owner: %s (%s)', $team->owner->name, $team->owner->email));
            $this->command->line(sprintf('     Members: %d', $team->allUsers()->count()));

            // Display members with roles - ИСПРАВЛЕНО
            $allMembers = $team->allUsers();

            foreach ($allMembers as $allMember) {
                // Проверяем, является ли пользователь владельцем
                $isOwner = $team->user_id === $allMember->id;

                if ($isOwner) {
                    // Владелец команды
                    $roleLabel = 'Owner';
                    $roleIcon = '👑';
                } else {
                    // Обычный член команды - получаем роль из pivot
                    $memberRole = $team->users()
                        ->wherePivot('user_id', $allMember->id)
                        ->first();

                    if ($memberRole && $memberRole->pivot && $memberRole->pivot->role) {
                        $roleEnum = RoleEnum::tryFrom($memberRole->pivot->role);
                        $roleLabel = $roleEnum ? $roleEnum->getLabel() : $memberRole->pivot->role;
                        $roleIcon = $this->getRoleIcon($memberRole->pivot->role);
                    } else {
                        $roleLabel = 'No Role';
                        $roleIcon = '❓';
                    }
                }

                $this->command->line(sprintf(
                    '       %s %s - %s',
                    $roleIcon,
                    $allMember->name,
                    $roleLabel
                ));
            }

            // Display pending invitations
            $invites = $team->teamInvitations()->get();
            if ($invites->count() > 0) {
                $this->command->line('     Pending Invitations:');
                foreach ($invites as $invite) {
                    $roleEnum = RoleEnum::tryFrom($invite->role);
                    $roleLabel = $roleEnum ? $roleEnum->getLabel() : $invite->role;
                    $this->command->line(sprintf(
                        '       ✉️  %s - %s',
                        $invite->email,
                        $roleLabel
                    ));
                }
            }

            $this->command->newLine();
        }

        $this->command->info('📋 Role Distribution:');
        $this->displayRoleDistribution();

        $this->command->newLine();
        $this->command->info('💡 Quick Start:');
        $this->command->line('   1. Login with admin@example.com / password');
        $this->command->line('   2. Navigate to Dashboard to see your teams');
        $this->command->line('   3. Use Team Switcher to change between teams');
        $this->command->line('   4. Check different role permissions');
        $this->command->newLine();
    }

    private function displayRoleDistribution(): void
    {
        $teams = Team::where('personal_team', false)->with('users')->get();

        $roleStats = [
            RoleEnum::ADMIN->value => 0,
            RoleEnum::EDITOR->value => 0,
            RoleEnum::VIEWER->value => 0,
        ];

        foreach ($teams as $team) {
            foreach ($team->users as $user) {
                if ($user->pivot && $user->pivot->role) {
                    $role = $user->pivot->role;
                    if (isset($roleStats[$role])) {
                        $roleStats[$role]++;
                    }
                }
            }
        }

        $tableData = [];
        foreach ($roleStats as $role => $count) {
            $roleEnum = RoleEnum::tryFrom($role);
            if ($roleEnum) {
                $tableData[] = [
                    $this->getRoleIcon($role).' '.$roleEnum->getLabel(),
                    $count,
                    $this->getProgressBar($count, array_sum($roleStats)),
                ];
            }
        }

        $this->command->table(
            ['Role', 'Count', 'Distribution'],
            $tableData
        );
    }

    private function getRoleIcon(string $role): string
    {
        return match ($role) {
            RoleEnum::ADMIN->value => '👑',
            RoleEnum::EDITOR->value => '✏️',
            RoleEnum::VIEWER->value => '👁️',
            default => '👤',
        };
    }

    private function getProgressBar(int $value, int $max): string
    {
        if ($max === 0) {
            return '';
        }

        $percentage = ($value / $max) * 100;
        $bars = (int) round($percentage / 10);

        return str_repeat('█', $bars).str_repeat('░', 10 - $bars).' '.number_format($percentage, 1).'%';
    }
}
