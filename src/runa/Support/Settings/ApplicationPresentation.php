<?php

namespace Runa\Support\Settings;

use Runa\Identity\ApplicationIdentity;
use Runa\Settings\SettingRegistry;
use Runa\Settings\SettingStore;

final readonly class ApplicationPresentation
{
    public function __construct(private ApplicationIdentity $identity, private SettingRegistry $registry, private SettingStore $store) {}

    /** @return array{name:string,logo:array{src:string,accessibleName:string}|null,revision:int} */
    public function read(): array
    {
        $state = $this->store->presentation();
        $definitions = $this->registry->settings();
        $name = $state['values']['presentation.name'] ?? null;
        $key = $state['values']['presentation.logo'] ?? null;
        $logo = is_string($key) ? ($this->registry->logos()[$key] ?? null) : null;

        return ['name' => is_string($name) && ($definitions['presentation.name'] ?? null)?->accepts($name) ? $name : $this->identity->applicationName,
            'logo' => $logo === null ? $this->identity->logo : ['src' => $logo->src, 'accessibleName' => $logo->accessibleName],
            'revision' => $state['revision']];
    }
}
