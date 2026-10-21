<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use Runa\Support\ConfigurationValidator;

final class ValidateConfiguration extends Command
{
    protected $signature = 'runa:config:validate';

    protected $description = 'Validate technical configuration without displaying secrets';

    public function handle(ConfigurationValidator $validator): int
    {
        $errors = $validator->errors();
        foreach ($errors as $error) {
            $this->error($error);
        }
        if ($errors !== []) {
            return self::FAILURE;
        }
        $this->info('Configuration is valid.');

        return self::SUCCESS;
    }
}
