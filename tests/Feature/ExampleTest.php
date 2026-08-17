<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_admin(): void
    {
        $this->get('/')->assertRedirect('/admin');
    }

    public function test_login_page_loads(): void
    {
        $this->get('/admin/login')->assertStatus(200);
    }

    public function test_authenticated_owner_can_load_every_panel_resource(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')->assertStatus(200);

        foreach (['/admin/organizations', '/admin/projects', '/admin/audit-events', '/admin/work-items', '/admin/agent-runs', '/admin/integrations'] as $url) {
            $this->actingAs($user)->get($url)->assertStatus(200);
        }
    }

    public function test_unauthenticated_api_request_gets_clean_401_not_a_redirect_crash(): void
    {
        // No Accept header, no token -- this is exactly the request shape that
        // used to crash with a 500 (tried to redirect to a non-existent 'login' route).
        $this->get('/api/work-items')->assertStatus(401);
        $this->get('/api/agent-runs')->assertStatus(401);
    }
}
