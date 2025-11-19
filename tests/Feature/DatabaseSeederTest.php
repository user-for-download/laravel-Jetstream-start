<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_runs_without_errors(): void
    {
        $this->expectsOutput();
        $this->expectsOutput();

        Artisan::call('db:seed');

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'name' => 'Admin User',
        ]);

        $this->assertDatabaseHas('teams', [
            'name' => 'Admin Team',
            'personal_team' => false,
        ]);
    }

    public function test_seeder_is_idempotent(): void
    {
        // Run once
        Artisan::call('db:seed');
        $firstRunCount = User::count();

        // Run again
        Artisan::call('db:seed');
        $secondRunCount = User::count();

        $this->assertEquals($firstRunCount, $secondRunCount);
        $this->assertGreaterThan(0, $firstRunCount);
    }
}
