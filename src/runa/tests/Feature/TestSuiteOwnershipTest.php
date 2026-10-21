<?php

use Symfony\Component\Process\Process;

it('keeps native application test discovery independent of private owner bootstraps', function (): void {
    $native = new Process([PHP_BINARY, 'artisan', 'test', '--list-tests'], base_path());
    $native->mustRun();
    expect($native->getOutput())->toContain('WelcomeTest')
        ->not->toContain('ManagementTest', 'DatabaseOwnershipTest', 'TestSuiteOwnershipTest');

    $owner = new Process([PHP_BINARY, 'vendor/bin/pest', '--configuration=runa/tests/phpunit.xml', '--test-directory=runa/tests', '--list-tests'], base_path());
    $owner->mustRun();
    expect($owner->getOutput())->toContain('ManagementTest', 'DatabaseOwnershipTest')
        ->not->toContain('WelcomeTest');
});
