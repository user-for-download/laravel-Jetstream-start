<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Provides transaction execution capability to services.
 *
 * This trait eliminates DRY violations in services that perform
 * multiple database transactions while maintaining explicit,
 * non-magical syntax.
 *
 * Usage: $this->transaction(fn () => YourModel::create([...]));
 */
trait ExecutesInTransaction
{
    /**
     * Execute callback within database transaction.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     *
     * @throws Throwable
     */
    protected function transaction(callable $callback, int $attempts = 1): mixed
    {
        return DB::transaction($callback, $attempts);
    }
}
