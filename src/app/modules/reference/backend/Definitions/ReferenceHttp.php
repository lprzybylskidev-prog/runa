<?php

namespace App\Modules\Reference\Definitions;

use App\Modules\Reference\Http\PingController;
use App\Modules\Reference\Http\ReferenceController;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\HttpAccess;
use Runa\Contracts\Modules\HttpEndpoint;
use Runa\Contracts\Modules\HttpProvider;

final class ReferenceHttp implements HttpProvider
{
    public function schemaVersion(): int
    {
        return 1;
    }

    public function endpoints(): array
    {
        return [
            new HttpEndpoint('GET', 'ping', new ApiOperation('reference.read'), PingController::class),
            ...array_map(static fn (array $entry) => new HttpEndpoint($entry[0], $entry[1],
                new ApiOperation($entry[2], 'app.reference.'.$entry[3]),
                ReferenceController::class, HttpAccess::Authenticated), [
                    ['GET', 'samples', 'samples.read', 'samples.read'],
                    ['PATCH', 'samples', 'samples.edit', 'samples.write'],
                    ['GET', 'settings', 'settings.read', 'settings.read'],
                    ['POST', 'settings/review', 'settings.review', 'settings.write'],
                    ['POST', 'settings/publish', 'settings.publish', 'settings.write'],
                    ['GET', 'snapshots', 'snapshots.read', 'settings.read'],
                    ['POST', 'snapshots', 'snapshots.capture', 'settings.write'],
                    ['DELETE', 'snapshots', 'snapshots.delete', 'settings.write'],
                    ['GET', 'history', 'history.read', 'history.read'],
                    ['GET', 'work', 'work.read', 'work.read'],
                    ['POST', 'work', 'work.dispatch', 'work.dispatch'],
                ]),
        ];
    }
}
