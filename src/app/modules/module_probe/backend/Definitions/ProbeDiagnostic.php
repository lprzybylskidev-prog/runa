<?php

namespace App\Modules\ModuleProbe\Definitions;

use Runa\Contracts\Modules\DiagnosticHandler;

final class ProbeDiagnostic implements DiagnosticHandler
{
    public function diagnose(array $payload): array
    {
        return ['module' => 'app.module_probe', 'status' => 'diagnostic_available'];
    }
}
