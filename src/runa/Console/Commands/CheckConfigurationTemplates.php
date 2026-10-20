<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use Runa\Support\ConfigurationValidator;

final class CheckConfigurationTemplates extends Command
{
    protected $signature = 'runa:config:templates';

    protected $description = 'Check environment example coverage without displaying secrets';

    public function handle(ConfigurationValidator $validator): int
    {
        $errors = $validator->templateErrors(app()->environmentPath());
        foreach ($errors as $error) {
            $this->error($error);
        }
        if ($errors !== []) {
            return self::FAILURE;
        }
        $this->info('Configuration templates are valid.');

        return self::SUCCESS;
    }
}
