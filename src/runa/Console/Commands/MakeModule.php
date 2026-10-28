<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use Runa\Development\Modules\ModuleGenerator;
use Throwable;

final class MakeModule extends Command
{
    protected $signature = 'runa:make:module {module : Full app.code or runa.code identity} {type=module : module/controller/model/migration/service/action/api/component/test} {name? : Simple PascalCase element name} {--write : Apply the displayed plan} {--overwrite=* : Exact repository-relative path=SHA256 authorization}';

    protected $description = 'Preview or generate owned module source and central documentation in development';

    public function handle(): int
    {
        try {
            $generator = new ModuleGenerator(dirname(base_path()), app()->environment(['local', 'testing']));
            $files = $generator->plan($this->argument('module'), $this->argument('type'), $this->argument('name'));
            $overwrites = [];
            foreach ($this->option('overwrite') as $authorization) {
                if (! is_string($authorization)) {
                    throw new \InvalidArgumentException('Overwrite authorization must be a string.');
                }
                [$path, $hash] = array_pad(explode('=', $authorization, 2), 2, '');
                if (preg_match('/\A[0-9a-f]{64}\z/D', $hash) !== 1 || isset($overwrites[$path])) {
                    throw new \InvalidArgumentException('Overwrite requires one path=SHA256 per existing file.');
                }
                $overwrites[$path] = $hash;
            }
            foreach ($files as $path => $contents) {
                $target = dirname(base_path()).'/'.$path;
                $this->line(json_encode(['path' => $path, 'current_sha256' => is_file($target) ? hash_file('sha256', $target) : null, 'candidate_sha256' => hash('sha256', $contents), 'content' => $contents], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
            }
            if ($this->option('write')) {
                $generator->write($files, $overwrites);
                $this->info('Module source generated. Review, format and test before synchronization; no module was registered or enabled.');
            }

            return self::SUCCESS;
        } catch (Throwable $error) {
            $this->error('Module generation refused: '.$error->getMessage());

            return self::FAILURE;
        }
    }
}
