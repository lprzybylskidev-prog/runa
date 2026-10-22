<?php

namespace Runa\Support\Modules\Storage;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Runa\Support\Modules\ModuleIdentity;
use RuntimeException;

final readonly class ModuleStorage
{
    public function __construct(private FilesystemManager $filesystems, private Repository $config) {}

    public function disk(string $id, string $visibility = 'private'): ModuleDisk
    {
        $module = new ModuleIdentity($id);
        if (! in_array($visibility, ['private', 'public'], true)) {
            throw new RuntimeException('Module storage visibility must be private or public.');
        }
        $name = ($module->owner === 'app' ? 'application' : 'runa').'_'.$visibility;
        $driver = $this->config->get('filesystems.disks.'.$name.'.driver');
        if (in_array($driver, ['scoped', 'read-through'], true)) {
            throw new RuntimeException('Module owner disks require a direct driver, not an indirect disk.');
        }
        if ($driver === 'local') {
            if ($this->config->get('filesystems.disks.'.$name.'.prefix')) {
                throw new RuntimeException('Local module disks must scope through their root, not an additional driver prefix.');
            }
            // The native adapter may create its root during construction, before a first write.
            LocalStorageBoundary::root($this->config->get('filesystems.disks.'.$name.'.root'));
        }
        $disk = $this->filesystems->disk($name);
        if (! $disk instanceof FilesystemAdapter) {
            throw new RuntimeException('Module storage requires a Flysystem-backed owner disk.');
        }
        $root = null;
        if ($disk->getAdapter() instanceof LocalFilesystemAdapter) {
            if ($disk->getConfig()['prefix'] ?? false) {
                throw new RuntimeException('Local module disks cannot hide an additional driver prefix.');
            }
            $root = LocalStorageBoundary::root($disk->getConfig()['root'] ?? null);
        } elseif ($driver === 'local' || ($disk->getConfig()['driver'] ?? null) === 'local') {
            // Unknown wrappers could hide a local adapter and defeat symbolic-link checks.
            throw new RuntimeException('Wrapped local storage requires an explicitly supported boundary.');
        }

        return new ModuleDisk($disk->getDriver(), $module, $root);
    }
}
