<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use Runa\Modules\Auth\Actions\OperatorAccounts;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Throwable;

/** Native trusted operator command; never queued, no infrastructure capability. */
final class ManageAuthOperator extends Command
{
    protected $signature = 'runa:auth:recover {--email= : Initial primary email} {--name= : Initial display name} {--login= : Initial login when required} {--user= : Existing administrator UUIDv7} {--revision= : Historical policy revision} {--output= : New private absolute handoff file in this PHP filesystem} {--confirm : Explicit operator confirmation}';

    protected $description = 'Initialize the first administrator or recover an existing administrator through a private one-time handoff';

    public function __construct(private readonly string $operation = 'recover')
    {
        if (! in_array($operation, ['initialize', 'recover', 'restore-policy'], true)) {
            throw new \InvalidArgumentException('Unknown Auth operator command.');
        }
        $this->signature = str_replace('runa:auth:recover', 'runa:auth:'.$operation, $this->signature);
        $this->description = match ($operation) {
            'initialize' => 'Initialize the first administrator through a private one-time setup file',
            'recover' => 'Recover an existing administrator through a private one-time setup file',
            'restore-policy' => 'Restore a historical Auth policy as a new revision without reversing executed effects',
        };
        parent::__construct();
    }

    public function handle(OperatorAccounts $accounts): int
    {
        $file = null;
        $created = false;
        try {
            $operation = $this->operation;
            if (! $this->option('confirm')) {
                $accounts->refused();
                $this->error('This changes administrator security state. Review operation and target; repeat with --confirm.');

                return self::FAILURE;
            }
            if ($operation === 'restore-policy') {
                $revision = $this->option('revision');
                if (! is_string($revision) || preg_match('/\A(?:0|[1-9][0-9]{0,15})\z/D', $revision) !== 1
                    || $this->option('output') !== null || $this->option('user') !== null || $this->option('email') !== null
                    || $this->option('name') !== null || $this->option('login') !== null) {
                    throw new \InvalidArgumentException;
                }
                $accounts->restorePolicy((int) $revision);
                $this->info('Policy restored as a new version; previous security effects remain in force.');

                return self::SUCCESS;
            }
            $path = $this->option('output');
            if (! is_string($path) || ! str_starts_with($path, '/') || str_contains($path, "\0")
                || realpath(dirname($path)) !== dirname($path) || is_link($path)
                || (fileperms(dirname($path)) & 0077) !== 0 || $this->option('revision') !== null) {
                throw new \InvalidArgumentException('Select a private absolute output directory.');
            }
            $mask = umask(0077);
            try {
                $file = @fopen($path, 'x');
            } finally {
                umask($mask);
            }
            if ($file === false) {
                throw new \RuntimeException('Private handoff file already exists or cannot be created.');
            }
            $created = true;
            app('db')->connection()->transaction(function () use ($operation, $accounts, $file): void {
                if ($operation === 'initialize') {
                    if ($this->option('user') !== null || ! is_string($this->option('email')) || ! is_string($this->option('name'))) {
                        throw new \InvalidArgumentException;
                    }
                    $material = $accounts->initialize(new NewIdentity($this->option('email'), name: $this->option('name'), login: $this->option('login')));
                } else {
                    if (! is_string($this->option('user')) || $this->option('email') !== null || $this->option('name') !== null || $this->option('login') !== null) {
                        throw new \InvalidArgumentException;
                    }
                    $material = $accounts->recover($this->option('user'));
                }
                $bytes = json_encode($material, JSON_THROW_ON_ERROR);
                if (fwrite($file, $bytes) !== strlen($bytes) || ! fflush($file)) {
                    throw new \RuntimeException('Private handoff write failed.');
                }
            });
            fclose($file);
            $file = null;
            $created = false;
            $this->info('Private one-time handoff written. It expires in 15 minutes; remove the file after use. No session was created.');

            return self::SUCCESS;
        } catch (Throwable) {
            if (is_resource($file)) {
                fclose($file);
            }
            if ($created) {
                @unlink($this->option('output'));
            }
            try {
                $accounts->refused();
            } catch (Throwable) {
                $this->error('Refusal Audit could not be persisted; restore module/Audit readiness.');
            }
            $this->error('Auth operator operation rejected. Check confirmation, target, module readiness, policy and private output path.');

            return self::FAILURE;
        }
    }
}
