<?php

namespace App\Console\Commands;

use App\Support\ConfigurationValidator;
use Illuminate\Console\Command;

final class ValidateConfiguration extends Command
{
    protected $signature = 'runa:config:validate {--templates : Also check environment example coverage}';

    protected $description = 'Validate technical configuration without displaying secrets';

    public function handle(ConfigurationValidator $validator): int
    {
        $errors = $validator->errors();
        if ($this->option('templates')) {
            $errors = array_merge($errors, $validator->templateErrors(base_path()));
        }
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
