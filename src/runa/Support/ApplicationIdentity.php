<?php

namespace Runa\Support;

use Illuminate\Contracts\Config\Repository;

/** The public projection intentionally excludes compatibility, paths and private configuration. */
final readonly class ApplicationIdentity
{
    public string $applicationId;

    public string $applicationName;

    public string $applicationVersion;

    public string $platformVersion;

    /** @var array{src: string, accessibleName: string}|null */
    public ?array $logo;

    public function __construct(Repository $config)
    {
        $this->applicationId = $config->string('branding.application_id');
        $this->applicationName = $config->string('branding.application_name');
        $this->applicationVersion = $config->string('branding.application_version');
        $this->platformVersion = $config->string('branding.platform_version');
        $src = $config->get('branding.logo.src');
        $alt = $config->get('branding.logo.accessibleName');
        $this->logo = is_string($src) && is_string($alt) ? ['src' => $src, 'accessibleName' => $alt] : null;
    }

    /** @return array{applicationId: string, applicationName: string, applicationVersion: string, platformVersion: string, logo?: array{src: string, accessibleName: string}} */
    public function toArray(): array
    {
        return ['applicationId' => $this->applicationId, 'applicationName' => $this->applicationName,
            'applicationVersion' => $this->applicationVersion, 'platformVersion' => $this->platformVersion]
            + ($this->logo === null ? [] : ['logo' => $this->logo]);
    }
}
