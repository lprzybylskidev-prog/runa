<?php

namespace Runa\Management\Actions;

use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

final class ConfigureXdebug
{
    public function execute(string $action, OutputInterface $output): int
    {
        $path = '/usr/local/etc/php/conf.d/zz-runa-xdebug.ini';
        if (! is_file($path)) {
            throw new InvalidArgumentException('Xdebug is available only in the development image.');
        }
        if ($action !== 'status') {
            $mode = $action === 'on' ? 'debug' : 'off';
            $source = file_get_contents($path);
            if ($source === false || file_put_contents($path, preg_replace('/^xdebug.mode = .*$/m', 'xdebug.mode = '.$mode, $source)) === false) {
                throw new InvalidArgumentException('Cannot update development Xdebug configuration.');
            }
            foreach (glob('/proc/[0-9]*/cmdline') ?: [] as $process) {
                $command = @file_get_contents($process);
                if (is_string($command) && str_starts_with($command, 'php-fpm: master process')) {
                    posix_kill((int) basename(dirname($process)), SIGUSR2);
                }
            }
        }

        // The current CLI process retains its startup ini; inspect a fresh process after a toggle.
        return (new RunTool)->process([PHP_BINARY, '-r', 'fwrite(STDOUT, "Xdebug ".phpversion("xdebug")." mode=".(ini_get("xdebug.mode") ?: "off").PHP_EOL);'], dirname(__DIR__, 4), $output);
    }
}
