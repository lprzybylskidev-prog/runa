<?php

namespace Tests\Fixtures;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Independent PostgreSQL backend; bounded contention, no sleeps or child-process inheritance. */
final class AggregateCompetition
{
    private Connection $connection;

    public function __construct()
    {
        config()->set('database.connections.admin_competitor', config('database.connections.'.DB::getDefaultConnection()));
        $this->connection = DB::connection('admin_competitor');
        $this->connection->statement("SET lock_timeout = '100ms'");
    }

    /** @param Closure(Connection): void $write */
    public function blocked(Closure $write): void
    {
        try {
            $write($this->connection);
        } catch (QueryException $error) {
            if ($error->getCode() === '55P03') {
                return;
            }
            throw $error;
        }
        throw new RuntimeException('Competing PostgreSQL writer bypassed aggregate locks.');
    }

    public function close(): void
    {
        DB::purge('admin_competitor');
    }
}
