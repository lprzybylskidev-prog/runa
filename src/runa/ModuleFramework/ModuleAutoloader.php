<?php

namespace Runa\ModuleFramework;

use Illuminate\Container\Container;
use RuntimeException;

final readonly class ModuleAutoloader
{
    public function __construct(private string $root, private ModuleRegistry $registry, private bool $development) {}

    public static function register(): void
    {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;
        spl_autoload_register(static function (string $class): void {
            if (str_starts_with($class, 'App\\Modules\\') || str_starts_with($class, 'Runa\\Modules\\')) {
                Container::getInstance()->make(self::class)->load($class);
            }
        }, prepend: true);
    }

    public function load(string $class): void
    {
        if (preg_match('/\A(App|Runa)\\\\Modules\\\\[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)+\z/D', $class) !== 1) {
            return;
        }
        foreach ($this->registry->snapshot()->modules as $record) {
            if (! $record->registered || ! $record->sourcePresent) {
                continue;
            }
            [$owner, $code] = explode('.', $record->id, 2);
            if (! in_array($owner, ['app', 'runa'], true) || ! ModuleManifest::validCode($code)) {
                throw new RuntimeException('Invalid persisted module identity.');
            }
            $prefix = ($owner === 'app' ? 'App' : 'Runa').'\\Modules\\'.str_replace(' ', '', ucwords(str_replace('_', ' ', $code))).'\\';
            if (! str_starts_with($class, $prefix)) {
                continue;
            }
            $root = ModuleDiscovery::ownedPath($this->root, $this->root.'/'.$owner.'/modules/'.$code);
            $file = $root.'/backend/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
            // Namespace aliases and absent symbols cannot load module code. Avoid
            // reading manifests while reflection probes such nonexistent classes.
            if (! file_exists($file) && ! is_link($file)) {
                return;
            }
            $manifest = ModuleManifest::load($root.'/module.json', $owner, $code, $this->development);
            if (hash_file('sha256', $root.'/module.json') !== $record->manifestHash || $manifest->kind !== $record->kind) {
                throw new RuntimeException('Module source changed; synchronize before loading it.');
            }
            require_once ModuleDiscovery::ownedPath($root, $file);

            return;
        }
    }
}
