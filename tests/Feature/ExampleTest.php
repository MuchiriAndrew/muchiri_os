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

        foreach (['/admin/organizations', '/admin/projects', '/admin/audit-events'] as $url) {
            $this->actingAs($user)->get($url)->assertStatus(200);
        }
    }
}
