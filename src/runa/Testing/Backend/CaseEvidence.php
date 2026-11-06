<?php

namespace Runa\Testing\Backend;

use RuntimeException;

/** Neutral development-test evidence. Never grants owner release acceptance. */
final class CaseEvidence
{
    /** @var resource */
    private $stream;

    private int $sequence = 0;

    private static ?self $instance = null;

    private function __construct(private readonly string $attempt, private readonly string $root)
    {
        $owner = getenv('RUNA_TEST_OWNER');
        if (! in_array($owner, ['application', 'runa'], true) || preg_match('/\A[a-f0-9]{32}\z/D', $attempt) !== 1) {
            throw new RuntimeException('Invalid test evidence identity.');
        }
        $directory = $root.'/.runtime/tests/backend/'.$owner.'/'.$attempt;
        if (realpath($directory) !== $directory || ! is_dir($directory)) {
            throw new RuntimeException('Test evidence requires its prepared owned directory.');
        }
        for ($path = $directory; $path !== $root; $path = dirname($path)) {
            $info = lstat($path);
            if ($info === false || is_link($path) || ($info['mode'] & 0022) !== 0 || (function_exists('posix_getuid') && $info['uid'] !== posix_getuid())) {
                throw new RuntimeException('Unsafe test evidence directory.');
            }
        }
        $stream = fopen($directory.'/'.getmypid().'.jsonl', 'x+b');
        if ($stream === false) {
            throw new RuntimeException('Cannot create unique native case evidence.');
        }
        chmod($directory.'/'.getmypid().'.jsonl', 0600);
        $this->stream = $stream;
    }

    public static function current(): ?self
    {
        $attempt = getenv('RUNA_TEST_ATTEMPT');
        if ($attempt === false || $attempt === '') {
            return null;
        }

        return self::$instance ??= new self($attempt, dirname(__DIR__, 4));
    }

    /** @param array<string, int|string|null> $fields */
    public function append(string $event, array $fields = []): void
    {
        if (++$this->sequence > 100000) {
            throw new RuntimeException('Native case evidence exceeded its bound.');
        }
        $token = getenv('TEST_TOKEN');
        $worker = is_string($token) && preg_match('/\A[1-9][0-9]*\z/D', $token) === 1 ? $token : null;
        $record = ['schema_version' => 1, 'attempt' => $this->attempt, 'pid' => getmypid(),
            'worker' => $worker, 'sequence' => $this->sequence, 'event' => $event] + $fields;
        $data = json_encode($record, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)."\n";
        if (strlen($data) > 65536) {
            throw new RuntimeException('Native case event exceeded its bound.');
        }
        for ($offset = 0, $length = strlen($data); $offset < $length; $offset += $written) {
            $written = fwrite($this->stream, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Cannot persist native case evidence.');
            }
        }
        if (! fflush($this->stream)) {
            throw new RuntimeException('Cannot flush native case evidence.');
        }
    }

    public function file(string $file): ?string
    {
        $actual = realpath($file);

        return is_string($actual) && str_starts_with($actual, $this->root.'/tests/backend/')
            ? substr($actual, strlen($this->root) + 1) : null;
    }
}
