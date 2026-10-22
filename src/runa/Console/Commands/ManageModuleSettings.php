<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use Runa\Contracts\Modules\SettingsActor;
use Runa\Support\Modules\Settings\ModuleSettings;
use Throwable;

final class ManageModuleSettings extends Command
{
    protected $signature = 'runa:modules:settings {module?} {--write : Read a JSON patch envelope from standard input} {--revision= : Expected settings revision} {--generation= : Expected registry generation} {--recover= : Recover an interrupted settings operation}';

    protected $description = 'Read redacted module settings or apply a revision-checked trusted settings change';

    public function handle(ModuleSettings $settings): int
    {
        try {
            $module = $this->argument('module');
            $recover = $this->option('recover');
            if (is_string($recover) && $recover !== '' && $module === null && ! $this->option('write')) {
                $result = $settings->recover($recover);
            } elseif (is_string($module) && $recover === null) {
                if (! $this->option('write')) {
                    $this->line(json_encode($settings->read($module), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

                    return self::SUCCESS;
                }
                $revision = $this->option('revision');
                $generation = $this->option('generation');
                if (! is_string($revision) || ! is_string($generation) || preg_match('/\A(?:0|[1-9][0-9]{0,15})\z/D', $revision) !== 1 || preg_match('/\A(?:0|[1-9][0-9]{0,15})\z/D', $generation) !== 1 || stream_isatty(STDIN)) {
                    throw new \RuntimeException('Expected revisions and piped JSON input.');
                }
                $json = stream_get_contents(STDIN, 65537);
                if ($json === false || strlen($json) > 65536) {
                    throw new \RuntimeException;
                }
                $input = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
                if (! is_array($input) || array_diff(array_keys($input), ['values', 'actor']) !== [] || ! is_array($input['values'] ?? null)) {
                    throw new \RuntimeException;
                }
                $values = [];
                foreach ($input['values'] as $key => $value) {
                    if (! is_string($key)) {
                        throw new \RuntimeException;
                    }
                    $values[$key] = $value;
                }
                $actor = $input['actor'] ?? null;
                if ($actor !== null && (! is_array($actor) || count($actor) !== 2 || ! is_string($actor['kind'] ?? null) || ! is_string($actor['id'] ?? null))) {
                    throw new \RuntimeException;
                }
                $result = $settings->update($module, $values, (int) $revision, (int) $generation, $actor === null ? null : new SettingsActor($actor['kind'], $actor['id']));
            } else {
                throw new \RuntimeException;
            }
            $this->line(json_encode($result, JSON_THROW_ON_ERROR));

            return $result->status === 'succeeded' || $result->status === 'failed' && $recover !== null ? self::SUCCESS : self::FAILURE;
        } catch (Throwable) {
            // No input, ciphertext, provider exception or secret-bearing validation message.
            $this->error('Module settings unavailable or change rejected. Check source, revisions, external key and unresolved operation status.');

            return self::FAILURE;
        }
    }
}
