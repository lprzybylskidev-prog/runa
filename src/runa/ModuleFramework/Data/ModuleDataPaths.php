<?php

namespace Runa\ModuleFramework\Data;

use Runa\ModuleFramework\ModuleDiscovery;
use Runa\ModuleFramework\ModuleManifest;
use Runa\Support\Modules\ModuleIdentity;
use Runa\Support\Modules\ModuleRecord;
use RuntimeException;

final readonly class ModuleDataPaths
{
    public function __construct(private string $root, private bool $development = false) {}

    /** @return list<string> */
    public function migrations(ModuleRecord $record): array
    {
        return $this->files($record, 'migrations');
    }

    /** @return list<string> */
    public function seeders(ModuleRecord $record): array
    {
        return $this->files($record, 'seeders');
    }

    /** @return list<string> */
    private function files(ModuleRecord $record, string $kind): array
    {
        if (! $record->registered || ! $record->sourcePresent) {
            throw new RuntimeException('Module data source is unavailable.');
        }
        $module = new ModuleIdentity($record->id);
        $source = ModuleDiscovery::ownedPath($this->root, $this->root.'/'.$module->owner.'/modules/'.$module->code);
        ModuleManifest::load($source.'/module.json', $module->owner, $module->code, $this->development);
        if ($record->sourcePath !== $module->owner.'/modules/'.$module->code || hash_file('sha256', $source.'/module.json') !== $record->manifestHash) {
            throw new RuntimeException('Module data source requires synchronization.');
        }
        $directory = $source.'/database/'.$kind;
        if (! file_exists($directory) && ! is_link($directory)) {
            return [];
        }
        ModuleDiscovery::ownedPath($source, $directory);
        $files = [];
        foreach (scandir($directory) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $path = ModuleDiscovery::ownedPath($directory, $directory.'/'.$name);
            if (! is_file($path) || ! str_ends_with($name, '.php')) {
                throw new RuntimeException('Module data paths support only regular PHP files.');
            }
            if ($kind === 'migrations' && preg_match('/\A[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}_'.$module->schema().'_[a-z][a-z0-9_]*\.php\z/D', $name) !== 1) {
                throw new RuntimeException('Module migration names must include their owner and code.');
            }
            $files[] = $path;
        }

        return $files;
    }
}
