<?php

namespace App\Modules\Reference\Definitions;

use Runa\Contracts\Modules\FrontendPage;
use Runa\Contracts\Modules\FrontendProvider;
use Runa\Contracts\Modules\SlotContribution;
use Runa\Contracts\Modules\SlotHost;
use Runa\Contracts\Modules\SlotProvider;

final class ReferenceFrontend implements FrontendProvider, SlotProvider
{
    public function pages(): array
    {
        return [new FrontendPage('panel', 'app.reference.panel', ['pl' => 'Moduł referencyjny', 'en' => 'Reference module'])];
    }

    public function hosts(): array
    {
        return [new SlotHost('app.reference.panel', 'reference.panel', 1)];
    }

    public function contributions(): array
    {
        return [new SlotContribution('app.reference.panel_content', new SlotHost('app.reference.panel', 'reference.panel', 1), 'app.reference.panel')];
    }
}
