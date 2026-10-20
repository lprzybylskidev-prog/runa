<?php

namespace Runa\Management;

use Illuminate\Console\OutputStyle;
use InvalidArgumentException;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class HostBridge
{
    /** @param list<string> $arguments */
    public function execute(string $root, string $command, array $arguments, bool $interactive, OutputInterface $output): int
    {
        $directory = $root.'/.runtime/host';
        if (is_dir('/run/runa-host')) {
            // Only the separate operator container receives this explicit mount.
            $directory = '/run/runa-host';
        }
        foreach ([$directory, dirname($directory), $directory.'/client.json', $directory.'/executor.sock'] as $path) {
            if (is_link($path)) {
                throw new InvalidArgumentException('Host executor paths must not be symlinks.');
            }
        }
        $path = $directory.'/client.json';
        $permissions = @fileperms($path);
        if (! is_file($path) || $permissions === false || ($permissions & 0077) !== 0 || filesize($path) > 4096) {
            throw new InvalidArgumentException('Host executor is unavailable. Run ./bin/artisan --recover-host from the owning host checkout.');
        }
        $content = file_get_contents($path);
        $credentials = $content !== false ? json_decode($content, true) : null;
        if (! is_array($credentials) || ! is_string($credentials['token'] ?? null) || ! is_string($credentials['workspace'] ?? null)) {
            throw new InvalidArgumentException('Invalid private host executor credentials; recover from the host.');
        }
        $socket = @stream_socket_client('unix://'.$directory.'/executor.sock', $errno, $error, 3);
        if ($socket === false) {
            throw new InvalidArgumentException('Host executor is unavailable. Run ./bin/artisan --recover-host from the owning host checkout.');
        }
        $native = $output instanceof OutputStyle ? $output->getOutput() : $output;
        $stderr = $native instanceof ConsoleOutputInterface ? $native->getErrorOutput() : $native;
        $id = bin2hex(random_bytes(16));
        $accepted = false;
        $stdinOpen = $interactive;
        $oldSignal = null;
        try {
            stream_set_timeout($socket, 15);
            $line = fgets($socket, 65538);
            $challenge = is_string($line) ? json_decode($line, true) : null;
            if (! is_array($challenge) || ($challenge['type'] ?? null) !== 'challenge' || ! is_string($challenge['value'] ?? null)) {
                throw new InvalidArgumentException('Host executor handshake failed.');
            }
            $this->send($socket, ['version' => 1, 'workspace' => $credentials['workspace'], 'token' => $credentials['token'],
                'challenge' => $challenge['value'], 'id' => $id, 'command' => $command, 'arguments' => $arguments, 'interactive' => $interactive]);
            if (function_exists('pcntl_signal_get_handler') && function_exists('pcntl_async_signals')) {
                $oldSignal = pcntl_signal_get_handler(SIGINT);
                pcntl_async_signals(true);
                pcntl_signal(SIGINT, function () use ($socket): void {
                    $this->send($socket, ['type' => 'cancel']);
                });
            }
            $buffer = '';
            while (true) {
                $read = [$socket];
                if ($accepted && $stdinOpen) {
                    $read[] = STDIN;
                }
                $write = $except = [];
                if (@stream_select($read, $write, $except, null) === false) {
                    continue;
                }
                foreach ($read as $stream) {
                    if ($stream === STDIN) {
                        $input = fread(STDIN, 4096);
                        if ($input === false || $input === '') {
                            $stdinOpen = false;
                            $this->send($socket, ['type' => 'eof']);
                        } else {
                            $this->send($socket, ['type' => 'stdin', 'data' => base64_encode($input)]);
                        }

                        continue;
                    }
                    $raw = fread($socket, 8192);
                    if ($raw === false || $raw === '') {
                        throw new InvalidArgumentException('Host connection lost. Operation '.$id.' may still be running. Use php artisan runa:host status '.$id.' after recovery; do not retry the mutation blindly.');
                    }
                    $buffer .= $raw;
                    if (strlen($buffer) > 65536) {
                        throw new InvalidArgumentException('Host response exceeds transport bounds.');
                    }
                    while (($end = strpos($buffer, "\n")) !== false) {
                        $frame = json_decode(substr($buffer, 0, $end), true);
                        $buffer = substr($buffer, $end + 1);
                        if (! is_array($frame)) {
                            throw new InvalidArgumentException('Malformed host response.');
                        }
                        $type = $frame['type'] ?? null;
                        if (in_array($type, ['stdout', 'stderr'], true) && is_string($frame['data'] ?? null)) {
                            $data = base64_decode($frame['data'], true);
                            if ($data === false) {
                                throw new InvalidArgumentException('Invalid host output encoding.');
                            }
                            ($type === 'stderr' ? $stderr : $native)->write($data, false, OutputInterface::OUTPUT_RAW);
                        } elseif ($type === 'accepted') {
                            $accepted = true;
                            $stderr->writeln('Host operation: '.$id);
                        } elseif ($type === 'exit' && is_int($frame['code'] ?? null)) {
                            return $frame['code'];
                        } elseif ($type === 'error') {
                            throw new InvalidArgumentException('Host request rejected. Recover the executor if its registry changed; inspect private operation receipts.');
                        } else {
                            throw new InvalidArgumentException('Unsupported host response.');
                        }
                    }
                }
            }
        } finally {
            if ($oldSignal !== null && function_exists('pcntl_signal')) {
                pcntl_signal(SIGINT, $oldSignal);
            }
            fclose($socket);
        }
    }

    /** @param resource $socket
     * @param  array<string, mixed>  $value
     */
    private function send($socket, array $value): void
    {
        $content = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n";
        while ($content !== '') {
            $written = @fwrite($socket, $content);
            if ($written === false || $written === 0) {
                throw new InvalidArgumentException('Host executor connection failed; inspect operation state before retrying.');
            }
            $content = substr($content, $written);
        }
    }
}
