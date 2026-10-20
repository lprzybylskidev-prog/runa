<?php

namespace Runa\Management;

use Illuminate\Console\Command;

final class DevelopmentCommand extends Command
{
    protected $signature = 'runa:dev
        {action : status or up (recover stopped application services)}
        {--plan : Print the host plan without executing it}
        {--host-request= : Internal transport request identity}';

    protected $description = 'Plan development status/startup; the host launcher executes the validated plan';

    public function handle(): int
    {
        $action = $this->argument('action');
        if (! in_array($action, ['status', 'up'], true)) {
            $this->error('Supported development actions: status, up.');

            return self::INVALID;
        }

        $request = $this->option('host-request');
        if ($request !== null && ! preg_match('/\A[a-f0-9]{32}\z/D', $request)) {
            $this->error('Invalid host request identity.');

            return self::INVALID;
        }

        $this->line(json_encode([
            'schema_version' => 1,
            'request_id' => $request,
            'operation' => 'development.'.$action,
            'parameters' => new \stdClass,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
