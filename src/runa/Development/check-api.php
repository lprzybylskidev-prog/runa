<?php

use Runa\Development\ApiSourceCheck;

// Fixed source-check adapter; never boots Laravel or imports customer implementations.
require dirname(__DIR__, 2).'/vendor/autoload.php';

$errors = (new ApiSourceCheck)->check(dirname(__DIR__, 3));
foreach ($errors as $error) {
    fwrite(STDERR, $error.PHP_EOL);
}
exit($errors === [] ? 0 : 1);
