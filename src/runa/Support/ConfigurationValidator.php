<?php

namespace Runa\Support;

use Illuminate\Contracts\Config\Repository;

final class ConfigurationValidator
{
    public function __construct(private readonly Repository $config) {}

    /** @return list<string> */
    public function errors(): array
    {
        $errors = [];
        $required = ['app.env', 'app.url', 'app.key', 'database.connections.pgsql.host',
            'database.connections.pgsql.database', 'database.connections.pgsql.username',
            'database.connections.pgsql.password'];
        foreach ($required as $key) {
            $value = $this->config->get($key);
            if (! is_string($value) || trim($value) === '') {
                $errors[] = $key.' must be a non-empty string.';
            }
        }
        if (! in_array($this->config->get('app.env'), ['local', 'testing', 'production'], true)) {
            $errors[] = 'app.env must be local, testing or production.';
        }
        foreach (['app.debug', 'runa.knowledge_enabled', 'mail.mailers.smtp.require_tls'] as $key) {
            if (! is_bool($this->config->get($key))) {
                $errors[] = $key.' must be boolean.';
            }
        }
        if ($this->config->get('database.default') !== 'pgsql') {
            $errors[] = 'database.default must be pgsql.';
        }
        if (! in_array($this->config->get('queue.default'), ['database', 'sync'], true)) {
            $errors[] = 'queue.default must be database or sync.';
        }
        $port = $this->config->get('database.connections.pgsql.port');
        if (filter_var($port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) === false) {
            $errors[] = 'database.connections.pgsql.port must be an integer between 1 and 65535.';
        }
        $mailer = $this->config->get('mail.default');
        if (! in_array($mailer, ['smtp', 'array'], true)) {
            $errors[] = 'mail.default must be smtp or array.';
        }
        if ($mailer === 'smtp') {
            $host = $this->config->get('mail.mailers.smtp.host');
            if (! is_string($host) || trim($host) === '' || preg_match('/[\s\/]/', $host)) {
                $errors[] = 'mail.mailers.smtp.host must be a non-empty hostname.';
            }
            if (filter_var($this->config->get('mail.mailers.smtp.port'), FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => 65535]]) === false) {
                $errors[] = 'mail.mailers.smtp.port must be a valid TCP port.';
            }
            if (! in_array($this->config->get('mail.mailers.smtp.scheme'), ['smtp', 'smtps'], true)) {
                $errors[] = 'mail.mailers.smtp.scheme must be smtp or smtps.';
            }
            $username = $this->config->get('mail.mailers.smtp.username');
            $password = $this->config->get('mail.mailers.smtp.password');
            if (($username !== null && ! is_string($username)) || ($password !== null && ! is_string($password))) {
                $errors[] = 'SMTP credentials must be strings or null.';
            }
            if ((! in_array($username, [null, ''], true)) !== (! in_array($password, [null, ''], true))) {
                $errors[] = 'SMTP username and password must be configured together.';
            }
        }
        if (filter_var($this->config->get('mail.from.address'), FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'mail.from.address must be a valid email address.';
        }
        $url = $this->config->get('app.url');
        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false
            || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            $errors[] = 'app.url must be an absolute HTTP or HTTPS URL.';
        }
        $key = $this->config->get('app.key');
        if (! is_string($key) || ! str_starts_with($key, 'base64:')
            || strlen((string) base64_decode(substr($key, 7), true)) !== 32) {
            $errors[] = 'app.key must contain a base64-encoded 32-byte key.';
        }
        if (! in_array($this->config->get('logging.channels.stderr.level'),
            ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'], true)) {
            $errors[] = 'logging.channels.stderr.level is invalid.';
        }
        if (! in_array($this->config->get('database.connections.pgsql.sslmode'),
            ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'], true)) {
            $errors[] = 'database.connections.pgsql.sslmode is invalid.';
        }
        if ($this->config->get('app.env') === 'production') {
            if ($this->config->get('mail.mailers.smtp.require_tls') !== true) {
                $errors[] = 'Production SMTP requires TLS.';
            }
            if ($mailer !== 'smtp' || in_array($this->config->get('mail.mailers.smtp.host'), ['mailpit', 'localhost', '127.0.0.1', 'smtp.example.com'], true)) {
                $errors[] = 'Production requires a configured outbound SMTP host, not Mailpit or localhost.';
            }
            if ($this->config->get('queue.default') !== 'database') {
                $errors[] = 'Production requires the database queue driver.';
            }
            if ($this->config->get('app.debug') !== false || $this->config->get('runa.knowledge_enabled') !== false) {
                $errors[] = 'Production requires debug and knowledge tooling to be disabled.';
            }
            if (! is_string($url) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
                $errors[] = 'Production requires an HTTPS app.url.';
            }
        }

        return $errors;
    }

    /** @return list<string> */
    public function templateErrors(string $root): array
    {
        $keys = [];
        foreach (glob($root.'/config/*.php') ?: [] as $path) {
            preg_match_all('/\benv\(\s*[\'"]([A-Z][A-Z0-9_]*)[\'"]/', (string) file_get_contents($path), $matches);
            $keys = array_merge($keys, $matches[1]);
        }
        $errors = [];
        foreach (['dev', 'test', 'prod'] as $environment) {
            $path = $root.'/.env.'.$environment.'.example';
            $text = is_file($path) ? (string) file_get_contents($path) : '';
            foreach (array_unique($keys) as $key) {
                if (! preg_match('/^'.preg_quote($key, '/').'=/m', $text)) {
                    $errors[] = basename($path).' is missing '.$key.'.';
                }
            }
        }

        return $errors;
    }
}
