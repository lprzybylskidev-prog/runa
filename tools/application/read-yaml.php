<?php

use Symfony\Component\Yaml\Yaml;

require dirname(__DIR__, 2).'/src/vendor/autoload.php';

echo json_encode(Yaml::parseFile($argv[1]), JSON_THROW_ON_ERROR);
