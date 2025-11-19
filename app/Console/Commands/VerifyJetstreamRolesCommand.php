<?php

// ========== app/Console/Commands/VerifyJetstreamRolesCommand.php ==========

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Command;
use Laravel\Jetstream\Jetstream;

class VerifyJetstreamRolesCommand extends Command
{
    protected $signature = 'jetstream:verify
                            {--user= : Verify specific user by email}
                            {--team= : Verify specific team by ID}
                            {--permissions : Show detailed permissions}
                            {--fix : Attempt to fix detected issues}
                            {--export= : Export report to file (json|csv|txt)}
                            {--check-integrity : Perform deep integrity check}
                            {--show-recommendations : Show security and optimization recommendations}';

    protected $description = 'Verify and diagnose Jetstream roles and permissions';

    private array $issues = [];

    private array $fixes = [];

    private array $recommendations = [];

    public function handle(): int
    {
        $this->displayHeader();

        if ($email = $this->option('user')) {
            return $this->verifyUser($email);
        }

        if ($teamId = $this->option('team')) {
            return $this->verifyTeam((int) $teamId);
        }

        $result = $this->verifyAll();

        if ($this->option('show-recommendations')) {
            $this->showRecommendations();
        }

        if ($exportFormat = $this->option('export')) {
            $this->exportReport($exportFormat);
        }

        return $result;
    }

    private function displayHeader(): void
    {
        $this->info('🔍 Jetstream Roles & Permissions Verification');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
    }

    private function verifyAll(): int
    {
        $this->verifyConfiguration();
        $this->verifyRoles();
        $this->verifyPermissions();
        $this->verifyTeams();
        $this->verifyUsers();

        if ($this->option('check-integrity')) {
            $this->performIntegrityCheck();
        }

        $this->displaySummary();

        return self::SUCCESS;
    }

    private function verifyConfiguration(): void
    {
        $this->info('📋 Configuration Check');
        $this->line('──────────────────────────────────────────────────────────────');

        $stack = config('jetstream.stack');
        $hasTeams = Jetstream::hasTeamFeatures();
        $guard = config('jetstream.guard');
        config('jetstream.features', []);

        $checks = [
            'Jetstream Stack' => $stack,
            'Teams Enabled' => $hasTeams ? '✓ Yes' : '✗ No',
            'Guard' => $guard,
            'Team Invitations' => $this->checkFeature('invitations') ? '✓ Enabled' : '✗ Disabled',
            'Profile Photos' => $this->checkFeature('profilePhotos') ? '✓ Enabled' : '✗ Disabled',
            'API Support' => $this->checkFeature('api') ? '✓ Enabled' : '✗ Disabled',
            'Account Deletion' => $this->checkFeature('accountDeletion') ? '✓ Enabled' : '✗ Disabled',
        ];

        $maxKeyLength = max(array_map(strlen(...), array_keys($checks)));

        foreach ($checks as $check => $value) {
            $color = str_contains((string) $value, '✓') ? 'green' : (str_contains((string) $value, '✗') ? 'yellow' : 'cyan');
            $this->line(sprintf('  %-'.$maxKeyLength.'s : <fg=%s>%s</>', $check, $color, $value));
        }

        // Check if roles are properly configured
        if (!$hasTeams) {
            $this->addIssue('configuration', 'Teams feature is not enabled but required for roles system');
        }

        $this->newLine();
    }

    private function checkFeature(string $feature): bool
    {
        $features = config('jetstream.features', []);

        foreach ($features as $configFeature) {
            if (is_string($configFeature) && str_contains($configFeature, $feature)) {
                return true;
            }

            if (is_array($configFeature) && isset($configFeature[0]) && str_contains((string) $configFeature[0], $feature)) {
                return true;
            }
        }

        return false;
    }

    private function verifyRoles(): void
    {
        $this->info('👔 Registered Roles');
        $this->line('──────────────────────────────────────────────────────────────');

        $roles = Jetstream::$roles;

        if (empty($roles)) {
            $this->error('  ✗ No roles registered!');
            $this->addIssue('roles', 'No roles are registered in JetstreamServiceProvider');
            $this->newLine();

            return;
        }

        $tableData = [];
        foreach ($roles as $role) {
            $permissionCount = count($role->permissions);
            $status = $permissionCount > 0 ? '<fg=green>✓</>' : '<fg=red>✗</>';

            $tableData[] = [
                $role->key,
                $role->name,
                $permissionCount,
                implode(', ', array_slice($role->permissions, 0, 3)).(count($role->permissions) > 3 ? '...' : ''),
                $status,
            ];

            // Check for roles without permissions
            if ($permissionCount === 0) {
                $this->addIssue('roles', sprintf("Role '%s' has no permissions assigned", $role->key));
            }
        }

        $this->table(['Key', 'Name', 'Perms', 'Sample Permissions', 'Status'], $tableData);

        // Verify Enum roles match Jetstream roles
        $this->verifyRoleEnumSync();

        $this->newLine();
    }

    private function verifyRoleEnumSync(): void
    {
        if (!class_exists(RoleEnum::class)) {
            $this->warn('  ⚠ RoleEnum class not found. Skipping enum sync check.');

            return;
        }

        $jetstreamRoles = array_keys(Jetstream::$roles);
        $enumRoles = RoleEnum::values();

        $missing = array_diff($enumRoles, $jetstreamRoles);
        $extra = array_diff($jetstreamRoles, $enumRoles);

        if ($missing !== []) {
            $this->warn('  ⚠ Roles in Enum but not in Jetstream: '.implode(', ', $missing));
            $this->addIssue('roles', 'Enum roles not registered in Jetstream: '.implode(', ', $missing));
        }

        if ($extra !== []) {
            $this->warn('  ⚠ Roles in Jetstream but not in Enum: '.implode(', ', $extra));
            $this->addRecommendation('Add missing roles to RoleEnum: '.implode(', ', $extra));
        }

        if ($missing === [] && $extra === []) {
            $this->line('  <fg=green>✓ Role Enum and Jetstream roles are synchronized</>');
        }
    }

    private function verifyPermissions(): void
    {
        $this->info('🔐 Permissions Overview');
        $this->line('──────────────────────────────────────────────────────────────');

        $allPermissions = collect(Jetstream::$roles)
            ->flatMap(fn ($role) => $role->permissions)
            ->unique()
            ->values()
            ->all();

        $defaultPermissions = Jetstream::$defaultPermissions;

        $this->line(sprintf('  Total Unique Permissions: <fg=cyan>%d</>', count($allPermissions)));
        $this->line(sprintf('  Default API Permissions: <fg=cyan>%s</>', implode(', ', $defaultPermissions)));
        $this->newLine();

        if ($this->option('permissions')) {
            $this->displayDetailedPermissions();
        }

        // Verify Permission Enum sync
        $this->verifyPermissionEnumSync($allPermissions);
    }

    private function displayDetailedPermissions(): void
    {
        $this->info('📝 Detailed Permissions by Role:');
        $this->line('──────────────────────────────────────────────────────────────');
        foreach (Jetstream::$roles as $role) {
            $this->line(sprintf('  <fg=yellow>%s (%s)</>', $role->name, $role->key));
            foreach ($role->permissions as $permission) {
                $this->line(sprintf('    • <fg=gray>%s</>', $permission));
            }

            $this->newLine();
        }
    }

    private function verifyPermissionEnumSync(array $jetstreamPermissions): void
    {
        if (!class_exists(PermissionEnum::class)) {
            $this->warn('  ⚠ PermissionEnum class not found. Skipping enum sync check.');

            return;
        }

        $enumPermissions = PermissionEnum::values();

        $missing = array_diff($enumPermissions, $jetstreamPermissions);
        $extra = array_diff($jetstreamPermissions, $enumPermissions);

        if ($missing !== []) {
            $this->warn('  ⚠ Permissions in Enum but not used: '.implode(', ', $missing));
            $this->addRecommendation('Consider assigning these permissions to roles: '.implode(', ', $missing));
        }

        if ($extra !== []) {
            $this->warn('  ⚠ Permissions in use but not in Enum: '.implode(', ', $extra));
            $this->addIssue('permissions', 'Unknown permissions in use: '.implode(', ', $extra));
        }

        if ($missing === [] && $extra === []) {
            $this->line('  <fg=green>✓ Permission Enum and used permissions are synchronized</>');
        }
    }

    private function verifyTeams(): void
    {
        $this->info('🏢 Teams Overview');
        $this->line('──────────────────────────────────────────────────────────────');

        $teams = Team::with(['users', 'owner'])->get();

        if ($teams->isEmpty()) {
            $this->warn('  No teams found.');
            $this->newLine();

            return;
        }

        $personalTeams = $teams->where('personal_team', true)->count();
        $sharedTeams = $teams->where('personal_team', false)->count();

        $this->line(sprintf('  Total Teams: <fg=cyan>%d</>', $teams->count()));
        $this->line(sprintf('  Personal Teams: <fg=cyan>%d</>', $personalTeams));
        $this->line(sprintf('  Shared Teams: <fg=cyan>%d</>', $sharedTeams));
        $this->newLine();

        $tableData = [];
        foreach ($teams->where('personal_team', false) as $team) {
            $memberCount = $team->allUsers()->count();
            $issueCount = count($this->checkTeamIssues($team));

            $status = $issueCount > 0 ? sprintf('<fg=red>⚠ %d issues</>', $issueCount) : '<fg=green>✓ OK</>';

            $tableData[] = [
                $team->id,
                $team->name,
                $team->owner->name,
                $memberCount,
                $status,
            ];
        }

        $this->table(['ID', 'Name', 'Owner', 'Members', 'Status'], $tableData);
        $this->newLine();
    }

    private function verifyUsers(): void
    {
        $this->info('👤 Users Overview');
        $this->line('──────────────────────────────────────────────────────────────');

        $totalUsers = User::count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $usersWithTeams = User::has('teams')->distinct()->count();
        $usersWithCurrentTeam = User::whereNotNull('current_team_id')->count();

        $stats = [
            'Total Users' => $totalUsers,
            'Verified Users' => sprintf('%d (%.1f%%)', $verifiedUsers, ($verifiedUsers / max($totalUsers, 1)) * 100),
            'Users in Teams' => sprintf('%d (%.1f%%)', $usersWithTeams, ($usersWithTeams / max($totalUsers, 1)) * 100),
            'With Current Team' => sprintf('%d (%.1f%%)', $usersWithCurrentTeam, ($usersWithCurrentTeam / max($totalUsers, 1)) * 100),
        ];

        $maxKeyLength = max(array_map(strlen(...), array_keys($stats)));

        foreach ($stats as $label => $value) {
            $this->line(sprintf('  %-'.$maxKeyLength.'s : <fg=cyan>%s</>', $label, $value));
        }

        // Check for users without current team
        $usersWithoutCurrentTeam = User::whereNull('current_team_id')
            ->whereHas('teams')
            ->count();

        if ($usersWithoutCurrentTeam > 0) {
            $this->warn(sprintf('  ⚠ %d users have teams but no current team set', $usersWithoutCurrentTeam));
            $this->addIssue('users', $usersWithoutCurrentTeam.' users missing current_team_id');

            if ($this->option('fix')) {
                $this->fixUsersWithoutCurrentTeam();
            }
        }

        $this->newLine();
    }

    private function performIntegrityCheck(): void
    {
        $this->info('🔬 Deep Integrity Check');
        $this->line('──────────────────────────────────────────────────────────────');

        $checks = [
            'Orphaned Team Members' => $this->checkOrphanedTeamMembers(),
            'Invalid Roles' => $this->checkInvalidRoles(),
            'Duplicate Team Memberships' => $this->checkDuplicateMemberships(),
            'Teams Without Owners' => $this->checkTeamsWithoutOwners(),
            'Invalid Current Teams' => $this->checkInvalidCurrentTeams(),
        ];

        $allPassed = true;

        foreach ($checks as $check => $result) {
            $status = $result['passed'] ? '<fg=green>✓ PASS</>' : '<fg=red>✗ FAIL</>';
            $this->line(sprintf('  %-30s : %s', $check, $status));

            if (!$result['passed']) {
                $allPassed = false;
                $this->line(sprintf('    <fg=yellow>%s</>', $result['message']));

                if ($this->option('fix') && isset($result['fix'])) {
                    $result['fix']();
                }
            }
        }

        if ($allPassed) {
            $this->line('  <fg=green>All integrity checks passed!</>');
        }

        $this->newLine();
    }

    private function checkOrphanedTeamMembers(): array
    {
        $orphaned = \DB::table('team_user')
            ->whereNotExists(function ($query): void {
                $query->select(\DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'team_user.user_id');
            })
            ->orWhereNotExists(function ($query): void {
                $query->select(\DB::raw(1))
                    ->from('teams')
                    ->whereColumn('teams.id', 'team_user.team_id');
            })
            ->count();

        return [
            'passed' => $orphaned === 0,
            'message' => sprintf('Found %s orphaned team member records', $orphaned),
            'fix' => function (): void {
                \DB::table('team_user')
                    ->whereNotExists(function ($query): void {
                        $query->select(\DB::raw(1))
                            ->from('users')
                            ->whereColumn('users.id', 'team_user.user_id');
                    })
                    ->orWhereNotExists(function ($query): void {
                        $query->select(\DB::raw(1))
                            ->from('teams')
                            ->whereColumn('teams.id', 'team_user.team_id');
                    })
                    ->delete();

                $this->fixes[] = 'Removed orphaned team member records';
                $this->line('    <fg=green>✓ Cleaned up orphaned team members</>');
            },
        ];
    }

    private function checkInvalidRoles(): array
    {
        $validRoles = array_keys(Jetstream::$roles);
        $invalidRoles = \DB::table('team_user')
            ->whereNotIn('role', $validRoles)
            ->whereNotNull('role')
            ->count();

        return [
            'passed' => $invalidRoles === 0,
            'message' => sprintf('Found %s team members with invalid roles', $invalidRoles),
            'fix' => function () use ($validRoles): void {
                // Set invalid roles to the first available role
                $defaultRole = $validRoles[0] ?? 'member';

                \DB::table('team_user')
                    ->whereNotIn('role', $validRoles)
                    ->whereNotNull('role')
                    ->update(['role' => $defaultRole]);

                $this->fixes[] = sprintf("Reset invalid roles to '%s'", $defaultRole);
                $this->line(sprintf("    <fg=green>✓ Reset invalid roles to '%s'</>", $defaultRole));
            },
        ];
    }

    private function checkDuplicateMemberships(): array
    {
        $duplicates = \DB::table('team_user')
            ->select('user_id', 'team_id', \DB::raw('COUNT(*) as count'))
            ->groupBy('user_id', 'team_id')
            ->having('count', '>', 1)
            ->count();

        return [
            'passed' => $duplicates === 0,
            'message' => sprintf('Found %s duplicate team memberships', $duplicates),
            'fix' => function (): void {
                // Keep only the first membership, delete duplicates
                $duplicates = \DB::table('team_user')
                    ->select('user_id', 'team_id', \DB::raw('MIN(id) as keep_id'))
                    ->groupBy('user_id', 'team_id')
                    ->having(\DB::raw('COUNT(*)'), '>', 1)
                    ->get();

                foreach ($duplicates as $duplicate) {
                    \DB::table('team_user')
                        ->where('user_id', $duplicate->user_id)
                        ->where('team_id', $duplicate->team_id)
                        ->where('id', '!=', $duplicate->keep_id)
                        ->delete();
                }

                $this->fixes[] = 'Removed duplicate team memberships';
                $this->line('    <fg=green>✓ Removed duplicate memberships</>');
            },
        ];
    }

    private function checkTeamsWithoutOwners(): array
    {
        $teamsWithoutOwners = Team::whereNotExists(function ($query): void {
            $query->select(\DB::raw(1))
                ->from('users')
                ->whereColumn('users.id', 'teams.user_id');
        })->count();

        return [
            'passed' => $teamsWithoutOwners === 0,
            'message' => sprintf('Found %s teams without valid owners', $teamsWithoutOwners),
        ];
    }

    private function checkInvalidCurrentTeams(): array
    {
        $invalidCurrentTeams = User::whereNotNull('current_team_id')
            ->whereNotExists(function ($query): void {
                $query->select(\DB::raw(1))
                    ->from('teams')
                    ->whereColumn('teams.id', 'users.current_team_id');
            })
            ->count();

        return [
            'passed' => $invalidCurrentTeams === 0,
            'message' => sprintf('Found %s users with invalid current_team_id', $invalidCurrentTeams),
            'fix' => function (): void {
                User::whereNotNull('current_team_id')
                    ->whereNotExists(function ($query): void {
                        $query->select(\DB::raw(1))
                            ->from('teams')
                            ->whereColumn('teams.id', 'users.current_team_id');
                    })
                    ->update(['current_team_id' => null]);

                $this->fixes[] = 'Reset invalid current_team_id values';
                $this->line('    <fg=green>✓ Reset invalid current team references</>');
            },
        ];
    }

    // ========== app/Console/Commands/VerifyJetstreamRolesCommand.php ==========
    // Замените метод checkTeamIssues (строки ~525-545)

    private function checkTeamIssues(Team $team): array
    {
        $issues = [];

        if (!$team->owner) {
            $issues[] = 'Team has no valid owner';
        }

        if ($team->allUsers()->count() === 0) {
            $issues[] = 'Team has no members (not even owner)';
        }

        // Check for members with invalid roles
        foreach ($team->users as $user) {
            if ($user->pivot && $user->pivot->role && !array_key_exists($user->pivot->role, Jetstream::$roles)) {
                $issues[] = sprintf('Member %s has invalid role: %s', $user->email, $user->pivot->role);
            }
        }

        return $issues;
    }

    private function verifyUser(string $email): int
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error('User not found: '.$email);

            return self::FAILURE;
        }

        $this->info('👤 User Information');
        $this->line('──────────────────────────────────────────────────────────────');
        $this->line(sprintf('  Name: <fg=cyan>%s</>', $user->name));
        $this->line(sprintf('  Email: <fg=cyan>%s</>', $user->email));
        $this->line(sprintf('  Verified: %s', $user->hasVerifiedEmail() ? '<fg=green>✓ Yes</>' : '<fg=red>✗ No</>'));
        $this->line(sprintf('  Current Team: <fg=cyan>%s</>', $user->currentTeam?->name ?? 'None'));
        $this->newLine();

        $teams = $user->allTeams();

        if ($teams->isEmpty()) {
            $this->warn('User is not a member of any teams.');

            return self::SUCCESS;
        }

        $this->info('🏢 Team Memberships');
        $this->line('──────────────────────────────────────────────────────────────');

        $tableData = [];
        foreach ($teams as $team) {
            $isOwner = $team->user_id === $user->id;
            $role = $isOwner ? '<fg=yellow>owner</>' : ($user->teamRole($team)?->key ?? 'N/A');
            $permissions = $isOwner ? 'All' : implode(', ', $user->teamPermissions($team));

            $tableData[] = [
                $team->name,
                $role,
                $isOwner ? '✓' : '',
                $team->id === $user->current_team_id ? '✓' : '',
                \Illuminate\Support\Str::limit($permissions, 40),
            ];
        }

        $this->table(['Team', 'Role', 'Owner', 'Current', 'Permissions'], $tableData);

        return self::SUCCESS;
    }

    private function verifyTeam(int $teamId): int
    {
        $team = Team::with(['users', 'owner'])->find($teamId);

        if (!$team) {
            $this->error('Team not found: '.$teamId);

            return self::FAILURE;
        }

        $this->info('🏢 Team Information');
        $this->line('──────────────────────────────────────────────────────────────');
        $this->line(sprintf('  Name: <fg=cyan>%s</>', $team->name));
        $this->line(sprintf('  Owner: <fg=cyan>%s (%s)</>', $team->owner->name, $team->owner->email));
        $this->line(sprintf('  Type: <fg=cyan>%s</>', $team->personal_team ? 'Personal' : 'Shared'));
        $this->line(sprintf('  Created: <fg=cyan>%s</>', $team->created_at->format('Y-m-d H:i:s')));
        $this->newLine();

        $issues = $this->checkTeamIssues($team);
        if ($issues !== []) {
            $this->warn('⚠ Issues detected:');
            foreach ($issues as $issue) {
                $this->line('  • '.$issue);
            }

            $this->newLine();
        }

        $this->info('👥 Team Members');
        $this->line('──────────────────────────────────────────────────────────────');

        $tableData = [];
        foreach ($team->allUsers() as $user) {
            $isOwner = $team->user_id === $user->id;
            $role = $isOwner ? '<fg=yellow>owner</>' : ($user->teamRole($team)?->key ?? 'N/A');
            $permissions = $isOwner ? 'All' : implode(', ', $user->teamPermissions($team));

            $tableData[] = [
                $user->name,
                $user->email,
                $role,
                \Illuminate\Support\Str::limit($permissions, 50),
                $user->pivot?->created_at?->format('Y-m-d') ?? 'N/A',
            ];
        }

        $this->table(['Name', 'Email', 'Role', 'Permissions', 'Joined'], $tableData);

        return self::SUCCESS;
    }

    private function fixUsersWithoutCurrentTeam(): void
    {
        $users = User::whereNull('current_team_id')
            ->whereHas('teams')
            ->with('teams')
            ->get();

        $fixed = 0;
        foreach ($users as $user) {
            $firstTeam = $user->teams->first();
            if ($firstTeam) {
                $user->forceFill(['current_team_id' => $firstTeam->id])->save();
                $fixed++;
            }
        }

        $this->fixes[] = sprintf('Set current_team_id for %d users', $fixed);
        $this->line(sprintf('    <fg=green>✓ Fixed %d users without current team</>', $fixed));
    }

    private function displaySummary(): void
    {
        $this->info('📊 Summary');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($this->issues === []) {
            $this->line('  <fg=green>✓ No issues detected. Everything looks good!</>');
        } else {
            $this->warn(sprintf('  ⚠ Found %d issue(s):', count($this->issues)));
            foreach ($this->issues as $category => $issueList) {
                $this->line(sprintf('    <fg=yellow>%s:</>', ucfirst((string) $category)));
                foreach ($issueList as $issue) {
                    $this->line(sprintf('      • %s', $issue));
                }
            }
        }

        if ($this->fixes !== []) {
            $this->newLine();
            $this->info(sprintf('  ✓ Applied %d fix(es):', count($this->fixes)));
            foreach ($this->fixes as $fix) {
                $this->line(sprintf('    • %s', $fix));
            }
        }

        $this->newLine();
    }

    private function showRecommendations(): void
    {
        $this->info('💡 Recommendations');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $defaultRecommendations = [
            'Regularly audit team memberships and roles',
            'Ensure all users have verified email addresses',
            'Use meaningful role names and descriptions',
            'Document your permission structure',
            'Implement role-based access control in your application policies',
            'Consider implementing audit logs for role changes',
        ];

        $allRecommendations = array_merge($this->recommendations, $defaultRecommendations);

        foreach ($allRecommendations as $allRecommendation) {
            $this->line(sprintf('  • %s', $allRecommendation));
        }

        $this->newLine();
    }

    private function exportReport(string $format): void
    {
        $data = [
            'timestamp' => now()->toIso8601String(),
            'configuration' => [
                'stack' => config('jetstream.stack'),
                'teams_enabled' => Jetstream::hasTeamFeatures(),
                'guard' => config('jetstream.guard'),
            ],
            'statistics' => [
                'users' => User::count(),
                'teams' => Team::count(),
                'roles' => count(Jetstream::$roles),
            ],
            'issues' => $this->issues,
            'fixes' => $this->fixes,
            'recommendations' => $this->recommendations,
        ];

        $filename = 'jetstream-report-'.now()->format('Y-m-d-His');

        match ($format) {
            'json' => $this->exportJson($data, $filename),
            'csv' => $this->exportCsv($data, $filename),
            'txt' => $this->exportTxt($data, $filename),
            default => $this->error('Unsupported export format: '.$format),
        };
    }

    private function exportJson(array $data, string $filename): void
    {
        $path = storage_path(sprintf('logs/%s.json', $filename));
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
        $this->info('Report exported to: '.$path);
    }

    private function exportCsv(array $data, string $filename): void
    {
        $path = storage_path(sprintf('logs/%s.csv', $filename));
        $fp = fopen($path, 'w');

        fputcsv($fp, ['Category', 'Type', 'Message']);

        foreach ($data['issues'] as $category => $issues) {
            foreach ($issues as $issue) {
                fputcsv($fp, ['Issue', $category, $issue]);
            }
        }

        foreach ($data['fixes'] as $fix) {
            fputcsv($fp, ['Fix', 'Applied', $fix]);
        }

        fclose($fp);
        $this->info('Report exported to: '.$path);
    }

    private function exportTxt(array $data, string $filename): void
    {
        $path = storage_path(sprintf('logs/%s.txt', $filename));
        $content = "Jetstream Verification Report\n";
        $content .= sprintf('Generated: %s%s', $data['timestamp'], PHP_EOL);
        $content .= str_repeat('=', 60)."\n\n";

        $content .= "CONFIGURATION\n";
        $content .= str_repeat('-', 60)."\n";
        foreach ($data['configuration'] as $key => $value) {
            $content .= sprintf("%-20s: %s\n", ucfirst(str_replace('_', ' ', $key)), is_bool($value) ? ($value ? 'Yes' : 'No') : $value);
        }

        $content .= "\nSTATISTICS\n";
        $content .= str_repeat('-', 60)."\n";
        foreach ($data['statistics'] as $key => $value) {
            $content .= sprintf("%-20s: %s\n", ucfirst((string) $key), $value);
        }

        if (!empty($data['issues'])) {
            $content .= "\nISSUES\n";
            $content .= str_repeat('-', 60)."\n";
            foreach ($data['issues'] as $category => $issues) {
                $content .= sprintf("\n%s:\n", ucfirst((string) $category));
                foreach ($issues as $issue) {
                    $content .= sprintf("  - %s\n", $issue);
                }
            }
        }

        if (!empty($data['fixes'])) {
            $content .= "\nFIXES APPLIED\n";
            $content .= str_repeat('-', 60)."\n";
            foreach ($data['fixes'] as $fix) {
                $content .= sprintf("  - %s\n", $fix);
            }
        }

        file_put_contents($path, $content);
        $this->info('Report exported to: '.$path);
    }

    private function addIssue(string $category, string $message): void
    {
        if (!isset($this->issues[$category])) {
            $this->issues[$category] = [];
        }

        $this->issues[$category][] = $message;
    }

    private function addRecommendation(string $recommendation): void
    {
        $this->recommendations[] = $recommendation;
    }
}
