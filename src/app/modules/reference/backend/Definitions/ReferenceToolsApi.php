<?php

namespace App\Modules\Reference\Definitions;

use App\Modules\Reference\Actions\ReferenceToolsHandler;
use App\Modules\Reference\Contracts\ReferenceTools;
use App\Modules\Reference\ReferenceToolsProxy;
use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;

final class ReferenceToolsApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(ReferenceTools::class, ReferenceToolsProxy::class, ReferenceToolsHandler::class, '1.0.0', [
            'samples' => new ApiOperation('samples.read', 'app.reference.samples.read'),
            'editSample' => new ApiOperation('samples.edit', 'app.reference.samples.write'),
            'settings' => new ApiOperation('settings.read', 'app.reference.settings.read'),
            'reviewSettings' => new ApiOperation('settings.review', 'app.reference.settings.write'),
            'publishSettings' => new ApiOperation('settings.publish', 'app.reference.settings.write'),
            'snapshots' => new ApiOperation('settings.snapshots', 'app.reference.settings.read'),
            'captureSnapshot' => new ApiOperation('settings.snapshots', 'app.reference.settings.write'),
            'deleteSnapshot' => new ApiOperation('settings.snapshots', 'app.reference.settings.write'),
            'history' => new ApiOperation('history.read', 'app.reference.history.read'),
            'dispatch' => new ApiOperation('work.dispatch', 'app.reference.work.dispatch'),
            'work' => new ApiOperation('work.read', 'app.reference.work.read'),
        ]);
    }
}
