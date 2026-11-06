<?php

namespace Runa\Support\Settings;

use Runa\Contracts\Settings\ApplicationImages;
use Runa\Contracts\Settings\ApplicationSettingProvider;
use Runa\Settings\SettingRegistry;
use Runa\Settings\SettingStore;

final readonly class ApplicationSettings
{
    public function __construct(private SettingRegistry $registry, private SettingStore $store) {}

    public function register(ApplicationSettingProvider $provider): void
    {
        $this->registry->register($provider);
    }

    /** @return array{revision:int,values:array<string,string|int|bool|null>} */
    public function read(): array
    {
        return $this->store->read();
    }

    /**
     * @param array<string,mixed> $patch
     * @return array{revision:int,values:array<string,string|int|bool|null>} */
    public function update(array $patch, int $expectedRevision, ?ApplicationImages $images = null): array
    {
        return $this->store->update($patch, $expectedRevision, $images);
    }
}
