<?php

use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter as DriverAdapter;
use League\Flysystem\FilesystemOperator;
use Runa\Support\Modules\Storage\ModuleDisk;
use Runa\Support\Modules\Storage\ModuleStorage;

beforeEach(function (): void {
    $this->root = sys_get_temp_dir().'/runa-module-storage-'.bin2hex(random_bytes(8));
    mkdir($this->root, 0775, true);
    foreach (['application', 'runa'] as $owner) {
        foreach (['private', 'public'] as $visibility) {
            $name = $owner.'_'.$visibility;
            config(['filesystems.disks.'.$name => ['driver' => 'local', 'root' => $this->root.'/'.$name, 'throw' => true]]);
            Storage::forgetDisk($name);
        }
    }
    $this->storage = app(ModuleStorage::class);
});

afterEach(function (): void {
    foreach (['application_private', 'application_public', 'runa_private', 'runa_public'] as $disk) {
        Storage::forgetDisk($disk);
    }
    File::deleteDirectory($this->root);
});

it('scopes strings streams copies moves listings and deletes by owner module and visibility', function (): void {
    foreach (['app.files', 'runa.files', 'app.other'] as $id) {
        foreach (['private', 'public'] as $visibility) {
            $disk = $this->storage->disk($id, $visibility);
            $disk->put('nested/a.txt', $id.':'.$visibility);
            expect($disk->get('nested/a.txt'))->toBe($id.':'.$visibility);
        }
    }
    $disk = $this->storage->disk('app.files');
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, 'stream payload');
    rewind($stream);
    $disk->writeStream('stream.txt', $stream);
    fclose($stream);
    $read = $disk->readStream('stream.txt');
    expect(stream_get_contents($read))->toBe('stream payload');
    fclose($read);
    $disk->copy('stream.txt', 'copy.txt');
    $disk->move('copy.txt', 'nested/moved.txt');
    expect($disk->files())->toBe(['nested/a.txt', 'nested/moved.txt', 'stream.txt'])
        ->and($disk->exists('copy.txt'))->toBeFalse()
        ->and($this->storage->disk('app.other')->exists('stream.txt'))->toBeFalse();
    $disk->deleteDirectory('nested');
    $disk->delete('stream.txt');
    expect($disk->files())->toBe([])
        ->and($this->storage->disk('runa.files')->get('nested/a.txt'))->toBe('runa.files:private')
        ->and($this->storage->disk('app.files', 'public')->get('nested/a.txt'))->toBe('app.files:public');
});

it('rejects traversal absolute paths streams alternate drivers and malformed segments in every operation', function (string $path): void {
    $disk = $this->storage->disk('app.files');
    foreach ([
        fn () => $disk->get($path), fn () => $disk->put($path, 'bad'), fn () => $disk->exists($path),
        fn () => $disk->delete($path), fn () => $disk->copy('safe', $path), fn () => $disk->move($path, 'safe'),
        fn () => $disk->files($path), fn () => $disk->deleteDirectory($path),
    ] as $operation) {
        expect($operation)->toThrow(InvalidArgumentException::class);
    }
})->with(['../other/secret', 'nested/../../secret', '/etc/passwd', 's3://bucket/key', 'php://filter', 'C:\\secret', 'nested\\..\\secret', '%2e%2e/secret', './secret', 'nested//file', 'nested/', ' nested', "bad\0name", "bad\nname"]);

it('rejects file directory ancestor and root symlinks without reading writing or deleting their targets', function (): void {
    $disk = $this->storage->disk('app.files');
    $disk->put('nested/a.txt', 'safe');
    mkdir($this->root.'/outside');
    file_put_contents($this->root.'/outside/secret', 'private');
    $base = $this->root.'/application_private/modules/app/files';
    symlink($this->root.'/outside/secret', $base.'/linked-file');
    symlink($this->root.'/outside', $base.'/linked-directory');
    symlink($this->root.'/missing', $base.'/dangling');
    foreach (['linked-file', 'linked-directory/secret', 'dangling'] as $path) {
        expect(fn () => $disk->get($path))->toThrow(RuntimeException::class);
        expect(fn () => $disk->put($path, 'overwrite'))->toThrow(RuntimeException::class);
        expect(fn () => $disk->delete($path))->toThrow(RuntimeException::class);
    }
    expect(fn () => $disk->files())->toThrow(RuntimeException::class);
    expect(fn () => $disk->deleteDirectory('linked-directory'))->toThrow(RuntimeException::class);
    symlink($this->root.'/outside', $base.'/nested/link');
    expect(fn () => $disk->deleteDirectory('nested'))->toThrow(RuntimeException::class);
    expect(file_get_contents($this->root.'/outside/secret'))->toBe('private');
    // A previously obtained disk must recheck ancestors on every call.
    rename($this->root.'/application_private/modules/app/files', $this->root.'/original');
    symlink($this->root.'/outside', $this->root.'/application_private/modules/app/files');
    expect(fn () => $disk->get('secret'))->toThrow(RuntimeException::class);
    $configuredRoot = $this->root.'/root-link';
    symlink($this->root.'/outside', $configuredRoot);
    config(['filesystems.disks.runa_private.root' => $configuredRoot]);
    Storage::forgetDisk('runa_private');
    expect(fn () => $this->storage->disk('runa.files')->put('escape', 'bad'))->toThrow(RuntimeException::class);
    config(['filesystems.disks.runa_private.root' => $configuredRoot.'/must-not-create']);
    Storage::forgetDisk('runa_private');
    expect(fn () => $this->storage->disk('runa.files'))->toThrow(RuntimeException::class);
    expect(is_dir($this->root.'/outside/must-not-create'))->toBeFalse();
});

it('uses the same prefixes with a nonlocal Flysystem driver and refuses escaped driver listings', function (): void {
    $operator = $this->createMock(FilesystemOperator::class);
    $adapter = $this->createStub(DriverAdapter::class);
    $manager = $this->createMock(FilesystemManager::class);
    $manager->expects($this->once())->method('disk')->with('application_private')->willReturn(new FilesystemAdapter($operator, $adapter, ['driver' => 'boundary_remote']));
    config(['filesystems.disks.application_private.driver' => 'boundary_remote']);
    $disk = (new ModuleStorage($manager, app(Repository::class)))->disk('app.files');
    $operator->expects($this->once())->method('write')->with('modules/app/files/a.txt', 'remote');
    $operator->expects($this->once())->method('read')->with('modules/app/files/a.txt')->willReturn('remote');
    $operator->expects($this->exactly(2))->method('listContents')->with('modules/app/files', true)->willReturnOnConsecutiveCalls(
        new DirectoryListing([new FileAttributes('modules/app/files/a.txt')]),
        new DirectoryListing([new FileAttributes('modules/app/other/secret')]),
    );
    $disk->put('a.txt', 'remote');
    expect($disk->get('a.txt'))->toBe('remote')->and($disk->files())->toBe(['a.txt']);
    expect(fn () => $disk->files())->toThrow(RuntimeException::class, 'outside');
});

it('cannot select arbitrary disks or delete the module root through its facade', function (): void {
    expect(fn () => $this->storage->disk('app.files', 'runa_private'))->toThrow(RuntimeException::class);
    $disk = $this->storage->disk('app.files');
    expect(fn () => $disk->deleteDirectory(''))->toThrow(InvalidArgumentException::class);
    expect(fn () => $disk->put('', 'bad'))->toThrow(InvalidArgumentException::class);
    expect(fn () => $disk->writeStream('safe', 'php://input'))->toThrow(InvalidArgumentException::class);
    expect(method_exists($disk, 'getAdapter'))->toBeFalse()->and(method_exists($disk, 'path'))->toBeTrue();
    expect((new ReflectionMethod(ModuleDisk::class, 'path'))->isPrivate())->toBeTrue();
});

it('rejects indirect disks and hidden local driver prefixes before resolving adapters', function (): void {
    $manager = $this->createMock(FilesystemManager::class);
    $manager->expects($this->never())->method('disk');
    $storage = new ModuleStorage($manager, app(Repository::class));
    foreach (['scoped', 'read-through'] as $driver) {
        config(['filesystems.disks.application_private.driver' => $driver]);
        expect(fn () => $storage->disk('app.files'))->toThrow(RuntimeException::class, 'direct driver');
    }
    config(['filesystems.disks.application_private.driver' => 'local', 'filesystems.disks.application_private.prefix' => '../elsewhere']);
    expect(fn () => $storage->disk('app.files'))->toThrow(RuntimeException::class, 'root');
});
