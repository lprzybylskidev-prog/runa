<?php

use Tests\TestCase;

pest()->extend(TestCase::class)->in(__DIR__.'/Feature');

require __DIR__.'/../../app/tests/Pest.php';
