<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Auth\Actions\StepUp;
use Runa\Modules\Auth\Contracts\AuthStepUp;
use Runa\Modules\Auth\StepUpProxy;

final class StepUpApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AuthStepUp::class, StepUpProxy::class, StepUp::class, '1.0.0', [
            'assertVerified' => new ApiOperation('step_up.assert'),
        ]);
    }
}
