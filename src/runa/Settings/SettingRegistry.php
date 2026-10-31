<?php

namespace Runa\Settings;

use InvalidArgumentException;
use Runa\Contracts\Settings\ApplicationLogo;
use Runa\Contracts\Settings\ApplicationSetting;
use Runa\Contracts\Settings\ApplicationSettingProvider;
use Runa\ModuleFramework\Management\ApplicationManagement;

/** Source catalogue only. Boot/freeze never touches persistence. */
final class SettingRegistry
{
    /** @var array<string,ApplicationSetting> */
    private array $settings = [];

    /** @var array<string,ApplicationLogo> */
    private array $logos = [];

    /** @var array<class-string,bool> */
    private array $providers = [];

    private bool $frozen = false;

    public function __construct(private readonly ApplicationManagement $source) {}

    public function register(ApplicationSettingProvider $provider): void
    {
        if ($this->frozen || isset($this->providers[$provider::class])) {
            throw new InvalidArgumentException('Application settings registration is frozen or duplicated.');
        }
        $this->source->inspect($provider::class);
        $settings = $provider->settings();
        $logos = $provider->logos();
        if (! array_is_list($settings) || ! array_is_list($logos) || count($settings) + count($this->settings) > 58
            || count($logos) + count($this->logos) > 32) {
            throw new InvalidArgumentException('Invalid application settings catalogue.');
        }
        $nextSettings = $this->settings;
        $nextLogos = $this->logos;
        foreach ($settings as $setting) {
            if (! $setting instanceof ApplicationSetting || str_starts_with($setting->key, 'presentation.') || isset($nextSettings[$setting->key])) {
                throw new InvalidArgumentException('Invalid or duplicate application setting.');
            }
            $nextSettings[$setting->key] = $setting;
        }
        foreach ($logos as $logo) {
            if (! $logo instanceof ApplicationLogo || $logo->key === 'uploaded' || isset($nextLogos[$logo->key])) {
                throw new InvalidArgumentException('Invalid or duplicate application logo.');
            }
            $nextLogos[$logo->key] = $logo;
        }
        $this->settings = $nextSettings;
        $this->logos = $nextLogos;
        $this->providers[$provider::class] = true;
    }

    public function freeze(): void
    {
        $this->frozen = true;
    }

    /** @return array<string,ApplicationSetting> */
    public function settings(): array
    {
        $this->freeze();
        $settings = $this->settings + [
            'presentation.locale' => new ApplicationSetting('presentation.locale', 'enum', 'pl', 'Default language', 'runa.admin.application.read', 'runa.admin.application.manage', choices: ['pl', 'en']),
            'presentation.theme' => new ApplicationSetting('presentation.theme', 'enum', 'system', 'Default theme', 'runa.admin.application.read', 'runa.admin.application.manage', choices: ['system', 'light', 'dark']),
            'presentation.timezone' => new ApplicationSetting('presentation.timezone', 'timezone', 'Europe/Warsaw', 'Default timezone', 'runa.admin.application.read', 'runa.admin.application.manage'),
            'presentation.favicon' => new ApplicationSetting('presentation.favicon', 'enum', 'automatic', 'Application favicon', 'runa.admin.application.read', 'runa.admin.application.manage', choices: ['automatic', 'uploaded']),
            'presentation.name' => new ApplicationSetting('presentation.name', 'string', null, 'Displayed application name', 'runa.admin.application.read', 'runa.admin.application.manage', 160),
            'presentation.logo' => new ApplicationSetting('presentation.logo', 'enum', null, 'Application logo', 'runa.admin.application.read', 'runa.admin.application.manage', choices: [...array_keys($this->logos), 'uploaded']),
        ];
        ksort($settings);

        return $settings;
    }

    /** @return array<string,ApplicationLogo> */
    public function logos(): array
    {
        $this->freeze();
        $logos = $this->logos;
        ksort($logos);

        return $logos;
    }
}
