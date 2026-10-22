<?php

namespace App\Modules\ModuleProbe\Definitions;

use Illuminate\Support\Facades\Log;
use Runa\Contracts\Modules\DiagnosticHandler;

final class ProbeDiagnostic implements DiagnosticHandler
{
    public function diagnose(array $payload): array
    {
        Log::info('Probe diagnostic observed.', ['code' => 'probe_diagnostic']);

        return ['module' => 'app.module_probe', 'status' => 'diagnostic_available'];
    }
}
