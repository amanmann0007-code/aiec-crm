<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_root_redirects_guests_to_login()
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
