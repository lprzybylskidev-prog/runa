<?php

use Illuminate\Console\Application as Artisan;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;

// No application kernel, .env, package discovery, HTTP providers, DB or key.
$container = new Application(dirname(__DIR__));
$console = new Artisan($container, new Dispatcher($container), Application::VERSION);
$console->setName('Runa management (Artisan)');
$console->resolveCommands(require __DIR__.'/commands.php');
$console->setCatchExceptions(true);

return $console;
