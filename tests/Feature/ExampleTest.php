<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_page_requires_authentication(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_health_route_is_available_without_authentication(): void
    {
        $this->get('/up')->assertOk();
    }
}
