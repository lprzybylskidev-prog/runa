<?php

use Runa\Management\NamedToolCommand;

require_once __DIR__.'/autoload.php';

$paths = [__DIR__.'/operations.json'];
if (is_file(__DIR__.'/development.json')) {
    $paths[] = __DIR__.'/development.json';
}
$commands = [];
foreach ($paths as $path) {
    $json = file_get_contents($path);
    if ($json === false) {
        throw new RuntimeException('Management command definitions are unavailable.');
    }
    /** @var list<array{name: string, tool: string, prefix: list<string>, signature: string, description: string}> $definitions */
    $definitions = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    foreach ($definitions as $definition) {
        $commands[] = new NamedToolCommand($definition);
    }
}

return $commands;
