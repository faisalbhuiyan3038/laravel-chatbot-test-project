<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test public chat endpoint response.
     */
    public function test_public_chat_page_loads_successfully(): void
    {
        $response = $this->get('/chat');

        $response->assertStatus(200);
    }
}
