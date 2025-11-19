<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Провайдер для регистрации Blade директив
 *
 * Registered Blade Directives:
 *
 * @see teamcan() @teamcan('permission') - Check team permission
 * @see teamrole() @teamrole('role') - Check team role
 * @see teamowner() @teamowner - Check if team owner
 * @see teamadmin() @teamadmin - Check if team admin
 * @see member() @member - Check team membership
 * @see personalteam() @personalteam - Check if personal team
 * @see currentteam() @currentteam - Display current team name
 * @see currentrole() @currentrole - Display current role name
 */
class BladeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerTeamDirectives();
    }

    /**
     * Регистрация директив для работы с командами
     *
     * Available directives:
     * - @teamcan('create') / @endteamcan
     * - @teamrole('admin') / @endteamrole
     * - @teamowner / @endteamowner
     * - @teamadmin / @endteamadmin
     * - @member / @endmember
     * - @personalteam / @endpersonalteam
     * - @currentteam
     * - @currentrole
     */
    protected function registerTeamDirectives(): void
    {
        // @teamcan('create')
        Blade::if('teamcan', team_can(...));

        // @teamrole('admin')
        Blade::if('teamrole', team_has_role(...));

        // @teamowner
        Blade::if('teamowner', is_team_owner(...));

        // @teamadmin
        Blade::if('teamadmin', is_team_admin(...));

        // @member (проверка членства в команде)
        Blade::if('member', static function (): bool {
            $user = auth()->user();
            $team = current_team();

            return $user && $team instanceof \App\Models\Team && $user->belongsToTeam($team);
        });

        // @personalteam
        Blade::if('personalteam', static function (): bool {
            $team = current_team();

            return $team instanceof \App\Models\Team && $team->isPersonal();
        });

        // @currentteam
        Blade::directive('currentteam', fn (): string => "<?php echo current_team()?->name ?? 'No Team'; ?>");

        // @currentrole
        Blade::directive('currentrole', fn (): string => "<?php echo current_team_role()?->name ?? 'No Role'; ?>");
    }
}
