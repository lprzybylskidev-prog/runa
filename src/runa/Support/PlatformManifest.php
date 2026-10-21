<?php

namespace Runa\Support;

use InvalidArgumentException;
use JsonException;

final readonly class PlatformManifest
{
    private function __construct(public string $id, public string $name, public string $version) {}

    public static function load(string $path): self
    {
        if (is_link($path) || ! is_file($path) || filesize($path) > 16384) {
            throw new InvalidArgumentException('Platform manifest must be a regular JSON file up to 16 KiB.');
        }
        $content = file_get_contents($path);
        if ($content === false) {
            throw new InvalidArgumentException('Platform manifest cannot be read.');
        }
        try {
            $data = json_decode($content, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Platform manifest must contain valid JSON.');
        }
        if (! is_array($data) || array_diff(array_keys($data), ['schema_version', 'id', 'name', 'version']) !== []
            || ($data['schema_version'] ?? null) !== 1 || ($data['id'] ?? null) !== 'runa' || ($data['name'] ?? null) !== 'Runa'
            || ! is_string($data['version'] ?? null)
            || preg_match('/\A(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\z/D', $data['version']) !== 1) {
            throw new InvalidArgumentException('Invalid platform manifest schema or identity/version.');
        }

        return new self($data['id'], $data['name'], $data['version']);
    }
}
