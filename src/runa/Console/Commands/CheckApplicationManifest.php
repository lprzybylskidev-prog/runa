<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Runa\Support\ApplicationManifest;
use Runa\Support\PlatformManifest;

final class CheckApplicationManifest extends Command
{
    protected $signature = 'runa:application:check {--platform-version= : Candidate stable Runa version}';

    protected $description = 'Validate the application manifest and its compatibility without changing source or runtime state';

    public function handle(): int
    {
        $target = $this->option('platform-version');
        try {
            $manifest = ApplicationManifest::load(app_path('application.json'));
            $platform = PlatformManifest::load(base_path('runa/platform.json'));
            $version = is_string($target) && $target !== '' ? $target : $platform->version;
            $manifest->assertCompatible($version);
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }
        $this->info('Application manifest is valid and compatible with Runa '.$version.'.');

        return self::SUCCESS;
    }
}
