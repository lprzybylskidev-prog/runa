<?php

namespace ApplicationTests\Feature;

use Tests\TestCase;

final class WelcomeTest extends TestCase
{
    public function test_application_welcome_page_is_available(): void
    {
        $this->get('/')->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}
