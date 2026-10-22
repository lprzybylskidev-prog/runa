<?php

namespace App\Modules\ModuleProbe\Definitions;

use Runa\Contracts\Modules\FrontendPage;
use Runa\Contracts\Modules\FrontendProvider;
use Runa\Contracts\Modules\SlotContribution;
use Runa\Contracts\Modules\SlotHost;
use Runa\Contracts\Modules\SlotProvider;

final class ProbeFrontend implements FrontendProvider, SlotProvider
{
    public function pages(): array
    {
        return [new FrontendPage('panel', 'app.module_probe.panel', ['pl' => 'Sonda modułów', 'en' => 'Module probe'])];
    }

    public function hosts(): array
    {
        return [new SlotHost('app.module_probe.panel', 'probe.panel', 1)];
    }

    public function contributions(): array
    {
        return [new SlotContribution('app.module_probe.panel_content', new SlotHost('app.module_probe.panel', 'probe.panel', 1), 'app.module_probe.panel')];
    }
}
