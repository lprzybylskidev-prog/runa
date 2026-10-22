<?php

namespace App\Modules\Reference\Definitions;

use Illuminate\Support\Facades\Log;
use Runa\Contracts\Modules\DiagnosticHandler;

final class ReferenceDiagnostic implements DiagnosticHandler
{
    public function diagnose(array $payload): array
    {
        Log::info('Reference diagnostic observed.', ['code' => 'reference_diagnostic']);

        return ['module' => 'app.reference', 'status' => 'diagnostic_available'];
    }
}
