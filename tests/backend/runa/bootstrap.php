<?php

require_once realpath(__DIR__.'/../../../src').'/vendor/autoload.php';
require_once __DIR__.'/PlatformTestCase.php';

// Private owner fixtures are deliberately absent from customer Composer autoloading.
spl_autoload_register(static function (string $class): void {
    $prefix = 'Tests\\Fixtures\\';
    if (str_starts_with($class, $prefix)) {
        $path = __DIR__.'/Fixtures/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
});
