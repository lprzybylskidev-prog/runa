<?php

namespace Runa\Support;

use InvalidArgumentException;
use JsonException;

final readonly class ApplicationManifest
{
    /** @param array{src: string, accessibleName: string}|null $logo */
    private function __construct(
        public string $id,
        public string $name,
        public string $version,
        public string $runa,
        public ?array $logo,
    ) {}

    public static function load(string $path): self
    {
        if (is_link($path) || ! is_file($path) || filesize($path) > 16384) {
            throw new InvalidArgumentException('Application manifest must be a regular JSON file up to 16 KiB.');
        }
        $content = file_get_contents($path);
        if ($content === false) {
            throw new InvalidArgumentException('Application manifest cannot be read.');
        }
        try {
            $data = json_decode($content, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Application manifest must contain valid JSON.');
        }
        if (! is_array($data) || array_diff(array_keys($data), ['schema_version', 'id', 'name', 'version', 'runa', 'branding']) !== []
            || ($data['schema_version'] ?? null) !== 1) {
            throw new InvalidArgumentException('Unsupported application manifest schema.');
        }
        foreach (['id', 'name', 'version', 'runa'] as $field) {
            if (! isset($data[$field]) || ! is_string($data[$field]) || trim($data[$field]) !== $data[$field]
                || $data[$field] === '' || strlen($data[$field]) > 256 || preg_match('/[\x00-\x1f\x7f]/', $data[$field])) {
                throw new InvalidArgumentException('Invalid application manifest field: '.$field.'.');
            }
        }
        if (preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/D', $data['id']) !== 1 || strlen($data['id']) > 64) {
            throw new InvalidArgumentException('Invalid stable application ID.');
        }
        if (! VersionCompatibility::validVersion($data['version'])) {
            throw new InvalidArgumentException('Application version must use SemVer.');
        }
        // Validate all grammar independently of the installed version.
        (new VersionCompatibility)->supports($data['runa'], '0.0.0');
        $logo = null;
        if (array_key_exists('branding', $data)) {
            $branding = $data['branding'];
            if (! is_array($branding) || array_diff(array_keys($branding), ['logo']) !== []) {
                throw new InvalidArgumentException('Invalid application branding metadata.');
            }
            if (array_key_exists('logo', $branding)) {
                $value = $branding['logo'];
                if (! is_array($value) || array_diff(array_keys($value), ['src', 'accessible_name']) !== []
                    || ! is_string($value['src'] ?? null) || ! self::safeLogo($value['src'])
                    || (isset($value['accessible_name']) && (! is_string($value['accessible_name']) || trim($value['accessible_name']) === ''
                        || strlen($value['accessible_name']) > 256 || preg_match('/[\x00-\x1f\x7f]/', $value['accessible_name'])))) {
                    throw new InvalidArgumentException('Invalid application logo metadata.');
                }
                $logo = ['src' => $value['src'], 'accessibleName' => $value['accessible_name'] ?? $data['name']];
            }
        }

        return new self($data['id'], $data['name'], $data['version'], $data['runa'], $logo);
    }

    public function assertCompatible(string $platformVersion): void
    {
        if (! (new VersionCompatibility)->supports($this->runa, $platformVersion)) {
            throw new InvalidArgumentException('Application manifest is incompatible with the selected Runa version.');
        }
    }

    private static function safeLogo(string $url): bool
    {
        if ($url === '' || strlen($url) > 2048 || preg_match('/[\s\\\\<>"\']/', $url)) {
            return false;
        }
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }
        $parts = parse_url($url);

        return filter_var($url, FILTER_VALIDATE_URL) !== false && is_array($parts)
            && ($parts['scheme'] ?? '') === 'https' && ! isset($parts['user']) && ! isset($parts['pass']);
    }
}
