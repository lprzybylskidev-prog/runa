<?php

namespace Runa\Management;

use InvalidArgumentException;
use JsonException;

final class DevelopmentContext
{
    public static function assertAllowed(string $root): void
    {
        $receipt = $root.'/.git/runa-bootstrap.json';
        if (is_link($root.'/.git') || is_link($receipt)) {
            throw new InvalidArgumentException('Development installation context must not traverse symlinks.');
        }
        if (is_file($receipt)) {
            $json = file_get_contents($receipt);
            try {
                $data = is_string($json) ? json_decode($json, true, flags: JSON_THROW_ON_ERROR) : null;
            } catch (JsonException) {
                throw new InvalidArgumentException('Invalid development installation context.');
            }
            if (! is_array($data) || (($data['schema_version'] ?? null) !== 1 || ($data['mode'] ?? null) !== 'dev')) {
                throw new InvalidArgumentException('Development tooling is blocked in production or unknown installation mode.');
            }
        }
        $environment = is_file($root.'/.env') ? file_get_contents($root.'/.env') : '';
        if (getenv('APP_ENV') === 'production' || (is_string($environment) && preg_match('/^\h*(?:export\h+)?APP_ENV\h*=\h*[\"\']?production[\"\']?\h*(?:#.*)?\r?$/m', $environment) === 1)) {
            throw new InvalidArgumentException('Development tooling is blocked in the production environment.');
        }
    }
}
