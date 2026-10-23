<?php

namespace App\Modules\Reference;

use App\Modules\Reference\Contracts\Ping;
use App\Modules\Reference\Contracts\PingResult;
use Illuminate\Support\Facades\Log;
use Runa\Support\Modules\Settings\ModuleSettings;

final class PingHandler implements Ping
{
    public function read(): PingResult
    {
        Log::info('Reference public API observed.', ['code' => 'reference_read']);
        $label = app(ModuleSettings::class)->value('app.reference', 'label');

        return new PingResult(is_string($label) ? $label : 'reference');
    }
}
