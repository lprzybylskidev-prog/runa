<?php

namespace Tests\Fixtures;

use Illuminate\Http\Request;
use Runa\Contracts\Modules\TransitionResult;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Explicit console fixture setup between HTTP requests; never an authenticated write. */
final class OperatorSettings
{
    /** @param array<string, mixed> $patch */
    public static function update(string $module, array $patch, int $revision, string $reviewId): TransitionResult
    {
        if (app(PrincipalScope::class)->current() !== null) {
            throw new \LogicException('Operator fixture refuses an authenticated principal.');
        }
        $request = app('request');
        app()->instance('request', Request::create('/'));
        try {
            return app(ModuleSettings::class)->update($module, $patch, $revision, $reviewId);
        } finally {
            app()->instance('request', $request);
        }
    }
}
