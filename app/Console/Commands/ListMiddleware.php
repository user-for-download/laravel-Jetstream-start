<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ListMiddleware extends Command
{
    protected $signature = 'middleware:list';

    protected $description = 'List all registered middleware aliases';

    public function handle(): int
    {
        app(\Illuminate\Contracts\Http\Kernel::class);

        $this->info('Registered Middleware Aliases:');
        $this->newLine();

        $middlewareAliases = [
            'team.member' => \App\Http\Middleware\EnsureUserBelongsToTeam::class,
            'team.role' => \App\Http\Middleware\EnsureUserHasTeamRole::class,
            'team.permission' => \App\Http\Middleware\EnsureUserHasTeamPermission::class,
            'team.owner' => \App\Http\Middleware\EnsureUserIsTeamOwner::class,
        ];

        $tableData = [];
        foreach ($middlewareAliases as $alias => $class) {
            $exists = class_exists($class) ? '✓' : '✗';
            $tableData[] = [$alias, $class, $exists];
        }

        $this->table(['Alias', 'Class', 'Exists'], $tableData);

        return self::SUCCESS;
    }
}
