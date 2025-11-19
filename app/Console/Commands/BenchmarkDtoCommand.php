<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\DataTransferObjects\User\CreateUserDto;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class BenchmarkDtoCommand extends Command
{
    protected $signature = 'benchmark:dto
                            {iterations=10000 : Number of iterations to run}
                            {--warmup=100 : Warmup iterations}';

    protected $description = 'Benchmark DTO conversion performance';

    public function handle(): int
    {
        $iterations = (int) $this->argument('iterations');
        $warmup = (int) $this->option('warmup');

        $this->info(sprintf('Benchmarking DTO performance with %d iterations...', $iterations));
        $this->newLine();

        // Warmup
        $this->info(sprintf('Running %d warmup iterations...', $warmup));
        for ($i = 0; $i < $warmup; $i++) {
            CreateUserDto::fromRequest([
                'name' => 'Warmup User',
                'email' => 'warmup@example.com',
                'password' => bin2hex(random_bytes(16)),
                'password_confirmation' => bin2hex(random_bytes(16)),
                'terms' => true,
            ]);
        }

        // Clear memory
        gc_collect_cycles();

        // Measure
        $memoryBefore = memory_get_usage(true);
        $start = hrtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $dto = CreateUserDto::fromRequest([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bin2hex(random_bytes(16)),
                'password_confirmation' => bin2hex(random_bytes(16)),
                'terms' => true,
            ]);

            // Also benchmark toArray
        }

        $elapsed = (hrtime(true) - $start) / 1e6; // Convert to milliseconds
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB

        // Display results
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Iterations', number_format($iterations)],
                ['Total Time', number_format($elapsed, 2).' ms'],
                ['Average Time', number_format($elapsed / $iterations * 1000, 3).' µs'],
                ['Memory Used', number_format($memoryUsed, 3).' MB'],
                ['Avg Memory/Iteration', number_format($memoryUsed / $iterations * 1024, 3).' KB'],
                ['Throughput', number_format($iterations / ($elapsed / 1000), 0).' ops/sec'],
            ]
        );

        $this->newLine();
        $this->info('✓ Benchmark completed successfully!');

        return CommandAlias::SUCCESS;
    }
}
