<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_redirects_to_play(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/play');
    }

    public function test_play_returns_shell(): void
    {
        $response = $this->get('/play');

        $response->assertOk();
    }

    public function test_sdk_bootstrap_returns_panel_data(): void
    {
        $response = $this->getJson('/api/sdk/bootstrap');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'server',
                'player',
                'wallet',
                'tabs',
                'features',
                'checkin',
                'changelog',
            ]);
    }

    public function test_standalone_functional_pages_are_not_exposed(): void
    {
        $this->get('/hall-of-fame')->assertNotFound();
        $this->get('/hall-of-fame/rankings')->assertNotFound();
        $this->get('/announcements/latest')->assertNotFound();
        $this->post('/announcements/ack')->assertNotFound();
    }
}
