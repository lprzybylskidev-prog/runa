<?php

use Symfony\Component\Process\Process;
use Tests\TestCase;

it('keeps native application test discovery independent of private owner bootstraps', function (): void {
    $native = new Process([PHP_BINARY, 'artisan', 'test', '--list-tests'], base_path());
    $native->mustRun();
    expect($native->getOutput())->toContain('WelcomeTest')
        ->not->toContain('ManagementTest', 'DatabaseOwnershipTest', 'TestSuiteOwnershipTest');

    $owner = new Process([PHP_BINARY, 'vendor/bin/pest', '--configuration=runa/tests/phpunit.xml', '--test-directory=runa/tests', '--list-tests'], base_path());
    $owner->mustRun();
    expect($owner->getOutput())->toContain('ManagementTest', 'DatabaseOwnershipTest')
        ->not->toContain('WelcomeTest');

    $pest = new Process([PHP_BINARY, 'vendor/bin/pest', '--test-directory=app/tests', '--list-tests'], base_path());
    $pest->mustRun();
    expect($pest->getOutput())->toContain('WelcomeTest')->not->toContain('ManagementTest');
    expect(is_dir(base_path('tests')))->toBeFalse()
        ->and((new ReflectionClass(TestCase::class))->getFileName())->toBe(base_path('runa/Testing/TestCase.php'));
});
