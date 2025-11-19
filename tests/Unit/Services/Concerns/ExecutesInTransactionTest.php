<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Concerns;

use App\Services\Concerns\ExecutesInTransaction;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExecutesInTransactionTest extends TestCase
{
    public function test_transaction_executes_callback(): void
    {
        // Simple mock: just execute the callback
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn ($callback) => $callback());

        $testClass = new class()
        {
            use ExecutesInTransaction;

            public function run(): string
            {
                return $this->transaction(fn (): string => 'success');
            }
        };

        $result = $testClass->run();

        $this->assertEquals('success', $result);
    }

    public function test_transaction_forwards_attempts_parameter(): void
    {
        // Verify the $attempts parameter is passed correctly
        DB::shouldReceive('transaction')
            ->once()
            ->with(\Mockery::any(), 3) // Match any closure and attempts=3
            ->andReturnUsing(fn ($callback) => $callback());

        $testClass = new class()
        {
            use ExecutesInTransaction;

            public function run(): string
            {
                return $this->transaction(fn (): string => 'test', 3);
            }
        };

        $result = $testClass->run();

        $this->assertEquals('test', $result);
    }
}
