<?php

namespace Runa\Support\Settings;

use Runa\Identity\ApplicationIdentity;
use Runa\Settings\SettingRegistry;
use Runa\Settings\SettingStore;

final readonly class ApplicationPresentation
{
    public function __construct(private ApplicationIdentity $identity, private SettingRegistry $registry, private SettingStore $store) {}

    /** @return array{name:string,logo:array{src:string,accessibleName:string}|null,favicon:?string,defaults:array<string,mixed>,revision:int} */
    public function read(): array
    {
        $state = $this->store->presentation();
        $definitions = $this->registry->settings();
        $name = $state['values']['presentation.name'] ?? null;
        $key = $state['values']['presentation.logo'] ?? null;
        $logo = is_string($key) ? ($this->registry->logos()[$key] ?? null) : null;

        $selectedName = is_string($name) && ($definitions['presentation.name'] ?? null)?->accepts($name) ? $name : $this->identity->applicationName;
        $url = static fn (string $kind): ?string => isset($state['assets'][$kind]) ? '/api/presentation/assets/'.$kind.'/'.$state['assets'][$kind].'.png' : null;
        $uploadedLogo = $key === 'uploaded' ? $url('logo') : null;
        $favicon = ($state['values']['presentation.favicon'] ?? null) === 'uploaded' ? $url('favicon') : null;
        $defaults = [];
        foreach (['locale', 'theme', 'timezone'] as $key) {
            $definition = $definitions['presentation.'.$key];
            $value = $state['values']['presentation.'.$key] ?? $definition->default;
            $defaults[$key] = $definition->accepts($value) ? $value : $definition->default;
        }

        return ['name' => $selectedName,
            'logo' => $uploadedLogo !== null ? ['src' => $uploadedLogo, 'accessibleName' => $selectedName] : ($logo === null ? $this->identity->logo : ['src' => $logo->src, 'accessibleName' => $logo->accessibleName]),
            'favicon' => $favicon ?? ($uploadedLogo !== null ? $url('logo_icon') : null),
            'defaults' => $defaults, 'revision' => $state['revision']];
    }
}
