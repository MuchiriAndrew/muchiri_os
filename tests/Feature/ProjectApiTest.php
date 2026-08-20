<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_project_with_a_repository(): void
    {
        $user = User::factory()->create();
        Organization::create(['name' => 'Test', 'slug' => 'test', 'kind' => 'personal', 'priority_weight' => 1]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/projects', [
            'name' => 'Axis Social',
            'organization' => 'test',
            'objective' => 'Grow the thing.',
            'created_by' => 'valkyrie',
            'repository' => [
                'name' => 'social',
                'provider' => 'github',
                'repository' => 'AXIS-LLC/social',
                'local_path' => '~/workprojects/axis-social',
                'default_branch' => 'main',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('project.slug', 'axis-social')
            ->assertJsonPath('project.status', 'active')
            ->assertJsonCount(1, 'repositories');

        $this->assertDatabaseHas('projects', ['slug' => 'axis-social']);
        $this->assertDatabaseHas('repositories', ['repository' => 'AXIS-LLC/social']);
    }

    public function test_creates_a_project_without_a_repository(): void
    {
        $user = User::factory()->create();
        Organization::create(['name' => 'Test', 'slug' => 'test', 'kind' => 'personal', 'priority_weight' => 1]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/projects', [
            'name' => 'New Idea',
            'organization' => 'test',
            'created_by' => 'valkyrie',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('project.slug', 'new-idea')
            ->assertJsonCount(0, 'repositories');
    }

    public function test_rejects_a_duplicate_slug(): void
    {
        $user = User::factory()->create();
        $org = Organization::create(['name' => 'Test', 'slug' => 'test', 'kind' => 'personal', 'priority_weight' => 1]);
        Project::create(['organization_id' => $org->id, 'name' => 'Existing', 'slug' => 'existing', 'status' => 'active']);

        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/projects', [
            'name' => 'Existing',
            'organization' => 'test',
            'created_by' => 'valkyrie',
        ])->assertStatus(422);
    }

    public function test_rejects_an_unknown_organization(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/projects', [
            'name' => 'Something',
            'organization' => 'does-not-exist',
            'created_by' => 'valkyrie',
        ])->assertStatus(422);
    }
}
