<?php

namespace Runa\Modules\Admin;

use Runa\Contracts\Modules\ManagementPageProvider;
use Runa\ModuleFramework\Management\ApplicationManagement;
use Runa\Modules\Admin\Contracts\AdminRegistry;

final readonly class Registry implements AdminRegistry
{
    public function __construct(private ApplicationManagement $registrations) {}

    public function register(ManagementPageProvider $provider): void
    {
        $this->registrations->register($provider);
    }
}
