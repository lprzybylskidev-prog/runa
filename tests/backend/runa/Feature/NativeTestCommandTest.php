<?php

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Runa\Testing\Backend\ApplicationTestCommand;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    $this->nativeRoot = dirname(base_path()).'/.runtime/tests/native-command-fixtures/'.bin2hex(random_bytes(8));
    mkdir($this->nativeRoot.'/src/vendor/bin', 0775, true);
    mkdir($this->nativeRoot.'/src/runa/testing/backend', 0775, true);
    file_put_contents($this->nativeRoot.'/src/phpunit.xml', '<phpunit/>');
    file_put_contents($this->nativeRoot.'/src/runa/testing/backend/worker-databases.php', '<?php file_put_contents(__DIR__."/../../../../cleanup.log", "cleanup\n", FILE_APPEND);');
    File::copy(dirname(__DIR__).'/Fixtures/native-command/runner.php.stub', $this->nativeRoot.'/src/vendor/bin/pest');
});

afterEach(function (): void {
    File::deleteDirectory($this->nativeRoot);
});

/** @return array{int, string} */
function runNativeCommandFixture(string $root, array $arguments, bool $literal = true): array
{
    $original = Container::getInstance();
    try {
        $laravel = new Application($root.'/src');
        $command = new ApplicationTestCommand;
        $command->setLaravel($laravel);
        $console = new ConsoleApplication;
        $console->setAutoExit(false);
        $console->setCatchExceptions(false);
        $console->addCommand($command);
        $output = new BufferedOutput;
        $input = $literal ? new ArgvInput(['artisan', 'test', '--without-tty', ...$arguments]) : new ArrayInput(['command' => 'test']);
        // Fixtures are noninteractive; never consume the surrounding worker protocol.
        $input->setInteractive(false);
        $status = $console->run($input, $output);

        return [$status, $output->fetch()];
    } finally {
        Container::setInstance($original);
    }
}

it('registers the owning native command without composing Collision', function (): void {
    expect(Artisan::all()['test'])->toBeInstanceOf(ApplicationTestCommand::class);
});

it('forwards literal native arguments and preserves process globals and failure exits', function (): void {
    $before = $_SERVER['argv'];
    $native = ['--filter', 'literal $(false); --parallel value', '--compact', '--profile', '--coverage', '--min=80', '--coverage-clover=coverage.xml', '--no-ansi', 'ExampleTest.php'];
    [$status, $output] = runNativeCommandFixture($this->nativeRoot, $native);
    $payload = json_decode(explode("\n", trim(str_replace("native stderr\n", '', $output)))[0], true, flags: JSON_THROW_ON_ERROR);
    expect($status)->toBe(7)->and($output)->toContain('native stderr')
        ->and($payload['arguments'])->toBe(['--configuration=phpunit.xml', '--test-directory=../tests/backend/app', ...array_map(fn ($value) => $value === '--no-ansi' ? '--colors=never' : $value, $native)])
        ->and($payload['environment']['APP_ENV'])->toBe('testing')
        ->and($payload['environment']['parallel'])->toBeFalse()
        ->and($_SERVER['argv'])->toBe($before)
        ->and(file_get_contents($this->nativeRoot.'/cleanup.log'))->toBe("cleanup\ncleanup\n");
});

it('uses one engine and explicit Laravel child flags in parallel mode', function (): void {
    $arguments = ['--filter=selected', '-p', '--processes', '2', '--recreate-databases', '--drop-databases', '--without-databases', '--without-cache', '--ansi'];
    [$status, $output] = runNativeCommandFixture($this->nativeRoot, $arguments);
    $payload = json_decode(trim(str_replace("native stderr\n", '', $output)), true, flags: JSON_THROW_ON_ERROR);
    expect($status)->toBe(7)->and($payload['arguments'])->toBe(['--configuration=phpunit.xml', '--test-directory=../tests/backend/app', '--filter=selected', '-p', '--processes', '2', '--recreate-databases', '--drop-databases', '--colors=always'])
        ->and($payload['environment'])->toMatchArray(['parallel' => '1', 'databases' => '1', 'cache' => '1', 'recreate' => '1', 'drop' => '1']);
});

it('keeps discovery independent of config cache and suite locks', function (string $flag): void {
    mkdir($this->nativeRoot.'/src/bootstrap/cache', 0775, true);
    file_put_contents($this->nativeRoot.'/src/bootstrap/cache/config.php', '<?php return [];');
    mkdir($this->nativeRoot.'/.runtime/tests', 0775, true);
    $lock = fopen($this->nativeRoot.'/.runtime/tests/suite.lock', 'c');
    flock($lock, LOCK_EX);
    try {
        [$status, $output] = runNativeCommandFixture($this->nativeRoot, ['--parallel', '--processes=2', '--without-cache', '--recreate-databases', $flag]);
        $payload = json_decode(trim(str_replace("native stderr\n", '', $output)), true, flags: JSON_THROW_ON_ERROR);
        expect($status)->toBe(7)->and($payload['arguments'])->toBe(['--configuration=phpunit.xml', '--test-directory=../tests/backend/app', $flag])
            ->and($payload['environment']['parallel'])->toBeFalse()
            ->and($payload['environment']['cache'])->toBeFalse()
            ->and(is_file($this->nativeRoot.'/cleanup.log'))->toBeFalse();
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
})->with(['--list-tests', '--list-tests-xml=tests.xml', '--list-suites', '--list-groups']);

it('refuses cached config and a held suite lock before starting the engine', function (): void {
    mkdir($this->nativeRoot.'/src/bootstrap/cache', 0775, true);
    file_put_contents($this->nativeRoot.'/src/bootstrap/cache/config.php', '<?php return [];');
    expect(fn () => runNativeCommandFixture($this->nativeRoot, []))->toThrow(RuntimeException::class, 'Clear cached');
    unlink($this->nativeRoot.'/src/bootstrap/cache/config.php');
    mkdir($this->nativeRoot.'/.runtime/tests', 0775, true);
    $lock = fopen($this->nativeRoot.'/.runtime/tests/suite.lock', 'c');
    flock($lock, LOCK_EX);
    try {
        expect(fn () => runNativeCommandFixture($this->nativeRoot, []))->toThrow(RuntimeException::class, 'Another test suite');
        expect(is_file($this->nativeRoot.'/cleanup.log'))->toBeFalse();
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
});

it('refuses non CLI invocation instead of reading the outer suite argv', function (): void {
    expect(fn () => runNativeCommandFixture($this->nativeRoot, [], literal: false))
        ->toThrow(RuntimeException::class, 'literal CLI arguments');
    expect(is_file($this->nativeRoot.'/cleanup.log'))->toBeFalse();
});

it('discards inherited development identity and stale parallel flags only in the child', function (): void {
    file_put_contents($this->nativeRoot.'/.env', "RUNA_NATIVE_FIXTURE_DEVELOPMENT=fixture\n");
    $development = getenv('RUNA_NATIVE_FIXTURE_DEVELOPMENT');
    $parallel = getenv('LARAVEL_PARALLEL_TESTING');
    putenv('RUNA_NATIVE_FIXTURE_DEVELOPMENT=fixture');
    putenv('LARAVEL_PARALLEL_TESTING=1');
    try {
        [$status, $output] = runNativeCommandFixture($this->nativeRoot, ['--env', 'local', '--list-tests', '--processes', '2']);
        $payload = json_decode(trim(str_replace("native stderr\n", '', $output)), true, flags: JSON_THROW_ON_ERROR);
        expect($status)->toBe(7)->and($payload['environment'])->toMatchArray(['APP_ENV' => 'testing', 'development' => false, 'parallel' => false, 'TEST_TOKEN' => false])
            ->and($payload['arguments'])->toBe(['--configuration=phpunit.xml', '--test-directory=../tests/backend/app', '--list-tests'])
            ->and(getenv('RUNA_NATIVE_FIXTURE_DEVELOPMENT'))->toBe('fixture');
    } finally {
        putenv($development === false ? 'RUNA_NATIVE_FIXTURE_DEVELOPMENT' : 'RUNA_NATIVE_FIXTURE_DEVELOPMENT='.$development);
        putenv($parallel === false ? 'LARAVEL_PARALLEL_TESTING' : 'LARAVEL_PARALLEL_TESTING='.$parallel);
    }
});

it('retains child signal exits and restores parent signal handlers after cleanup', function (): void {
    file_put_contents($this->nativeRoot.'/src/vendor/bin/pest', '<?php posix_kill(getmypid(), SIGTERM);');
    $handler = pcntl_signal_get_handler(SIGTERM);
    [$status] = runNativeCommandFixture($this->nativeRoot, []);
    expect($status)->toBe(143)->and(pcntl_signal_get_handler(SIGTERM))->toBe($handler)
        ->and(file_get_contents($this->nativeRoot.'/cleanup.log'))->toBe("cleanup\ncleanup\n");
});

it('forwards parent interruption to the child and completes guarded cleanup', function (): void {
    file_put_contents($this->nativeRoot.'/src/vendor/bin/pest', '<?php posix_kill(posix_getppid(), SIGINT); sleep(5);');
    $handler = pcntl_signal_get_handler(SIGINT);
    [$status] = runNativeCommandFixture($this->nativeRoot, []);
    expect($status)->toBe(130)->and(pcntl_signal_get_handler(SIGINT))->toBe($handler)
        ->and(file_get_contents($this->nativeRoot.'/cleanup.log'))->toBe("cleanup\ncleanup\n");
});

it('preserves native positional arguments after the option delimiter', function (): void {
    [$status, $output] = runNativeCommandFixture($this->nativeRoot, ['--', '--parallel', '--list-tests', '--ansi']);
    $payload = json_decode(trim(str_replace("native stderr\n", '', $output)), true, flags: JSON_THROW_ON_ERROR);
    expect($status)->toBe(7)->and($payload['arguments'])->toBe(['--configuration=phpunit.xml', '--test-directory=../tests/backend/app', '--', '--parallel', '--list-tests', '--ansi'])
        ->and($payload['environment']['parallel'])->toBeFalse()
        ->and(file_get_contents($this->nativeRoot.'/cleanup.log'))->toBe("cleanup\ncleanup\n");
});

it('refuses execution when guarded preflight cleanup fails', function (): void {
    file_put_contents($this->nativeRoot.'/src/runa/testing/backend/worker-databases.php', '<?php exit(9);');
    [$status, $output] = runNativeCommandFixture($this->nativeRoot, []);
    expect($status)->toBe(9)->and($output)->toBe('');
});

it('retains cleanup failure after a successful engine exit', function (): void {
    file_put_contents($this->nativeRoot.'/src/vendor/bin/pest', '<?php exit(0);');
    file_put_contents($this->nativeRoot.'/src/runa/testing/backend/worker-databases.php', '<?php $path = __DIR__."/../../../../cleanup.log"; if (is_file($path)) { exit(9); } file_put_contents($path, "cleanup");');
    [$status] = runNativeCommandFixture($this->nativeRoot, []);
    expect($status)->toBe(9);
});

it('preserves an explicit native configuration without duplicate defaults', function (array $arguments): void {
    [$status, $output] = runNativeCommandFixture($this->nativeRoot, [...$arguments, '--list-tests']);
    $payload = json_decode(trim(str_replace("native stderr\n", '', $output)), true, flags: JSON_THROW_ON_ERROR);
    expect($status)->toBe(7)->and($payload['arguments'])->toBe(['--test-directory=../tests/backend/app', ...$arguments, '--list-tests']);
})->with([[['--configuration=custom.xml']], [['--configuration', 'custom.xml']], [['-c', 'custom.xml']], [['-ccustom.xml']], [['--no-configuration']]]);
