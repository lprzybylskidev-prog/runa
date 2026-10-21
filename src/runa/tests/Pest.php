<?php

use Tests\PlatformTestCase;

require_once __DIR__.'/bootstrap.php';

pest()->extend(PlatformTestCase::class)->in(__DIR__.'/Feature');
