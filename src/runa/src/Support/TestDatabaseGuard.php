<?php

namespace Runa\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\DatabaseManager;
use RuntimeException;

final class TestDatabaseGuard
{
    public function __construct(
        private readonly Repository $config,
        private readonly DatabaseManager $database,
    ) {}

    public function assertSafe(): void
    {
        $connection = $this->config->get('database.default');
        $settings = $this->config->get('database.connections.pgsql');
        if ($this->config->get('app.env') !== 'testing' || $connection !== 'pgsql'
            || ! is_array($settings) || ($settings['driver'] ?? null) !== 'pgsql'
            || ($settings['host'] ?? null) !== $this->config->get('runa.test_database_host')
            || ($settings['username'] ?? null) !== 'runa_test'
            || ! is_string($settings['database'] ?? null)
            || ! preg_match('/\Aruna_test(?:_(?:test_)?[1-9][0-9]*)?\z/D', $settings['database'])) {
            throw new RuntimeException('Destructive database operations require the isolated PostgreSQL testing environment, host, role and database.');
        }
        $actual = $this->database->connection('pgsql')->selectOne('SELECT current_database() AS name, session_user AS username');
        if (! $actual instanceof \stdClass || $actual->name !== $settings['database'] || $actual->username !== 'runa_test') {
            throw new RuntimeException('Actual test database identity does not match the guarded configuration.');
        }
    }
}
