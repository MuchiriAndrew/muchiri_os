<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TodoistDirectAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_projects_across_every_connected_organization_when_none_specified(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a', 'kind' => 'personal', 'priority_weight' => 1]);
        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b', 'kind' => 'personal', 'priority_weight' => 1]);
        Integration::create(['organization_id' => $orgA->id, 'provider' => 'todoist', 'credentials' => ['token' => 'token-a'], 'is_active' => true]);
        Integration::create(['organization_id' => $orgB->id, 'provider' => 'todoist', 'credentials' => ['token' => 'token-b'], 'is_active' => true]);

        Http::fake([
            'api.todoist.com/api/v1/projects' => Http::sequence()
                ->push(['results' => [['id' => 'p-a', 'name' => 'Blueband Flora']]])
                ->push(['results' => [['id' => 'p-b', 'name' => 'Something Else']]]),
        ]);

        $response = $this->getJson('/api/todoist/projects')->assertStatus(200);

        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Blueband Flora', 'organization' => 'org-a']);
        $response->assertJsonFragment(['name' => 'Something Else', 'organization' => 'org-b']);
    }

    public function test_finds_a_project_by_fuzzy_name_in_whichever_organization_has_it(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a', 'kind' => 'personal', 'priority_weight' => 1]);
        Integration::create(['organization_id' => $orgA->id, 'provider' => 'todoist', 'credentials' => ['token' => 'token-a'], 'is_active' => true]);

        Http::fake([
            'api.todoist.com/api/v1/projects' => Http::response(['results' => [['id' => 'p-a', 'name' => 'Blueband Flora']]]),
            'api.todoist.com/api/v1/projects/p-a/collaborators' => Http::response(['results' => []]),
            'api.todoist.com/api/v1/tasks*' => Http::response(['results' => [
                ['id' => 'task-1', 'content' => 'Water the plants', 'description' => '', 'checked' => false],
            ]]),
        ]);

        $response = $this->getJson('/api/todoist/tasks?project=blueband')->assertStatus(200);

        $response->assertJsonCount(1);
        $response->assertJsonFragment(['content' => 'Water the plants', 'project' => 'Blueband Flora', 'organization' => 'org-a']);
    }

    public function test_returns_empty_when_no_organization_has_a_matching_project(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a', 'kind' => 'personal', 'priority_weight' => 1]);
        Integration::create(['organization_id' => $orgA->id, 'provider' => 'todoist', 'credentials' => ['token' => 'token-a'], 'is_active' => true]);

        Http::fake([
            'api.todoist.com/api/v1/projects' => Http::response(['results' => [['id' => 'p-a', 'name' => 'Something Unrelated']]]),
        ]);

        $this->getJson('/api/todoist/tasks?project=blueband')->assertStatus(200)->assertJson([]);
    }

    public function test_all_open_tasks_for_self_uses_the_todoist_filter_endpoint(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $org = Organization::create(['name' => 'Org', 'slug' => 'org', 'kind' => 'personal', 'priority_weight' => 1]);
        Integration::create(['organization_id' => $org->id, 'provider' => 'todoist', 'credentials' => ['token' => 'token'], 'is_active' => true]);

        Http::fake([
            'api.todoist.com/api/v1/projects' => Http::response(['results' => [['id' => 'p-1', 'name' => 'Some Project']]]),
            'api.todoist.com/api/v1/tasks/filter*' => Http::response(['results' => [
                ['id' => 'task-1', 'content' => 'My task', 'description' => '', 'checked' => false, 'project_id' => 'p-1'],
            ]]),
        ]);

        $response = $this->getJson('/api/todoist/tasks?organization=org')->assertStatus(200);

        $response->assertJsonCount(1);
        $response->assertJsonFragment(['content' => 'My task', 'project' => 'Some Project']);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/tasks/filter') && $request['query'] === 'assigned to: me');
    }

    public function test_all_open_tasks_for_a_named_other_person_resolves_via_collaborators(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $org = Organization::create(['name' => 'Org', 'slug' => 'org', 'kind' => 'personal', 'priority_weight' => 1]);
        Integration::create(['organization_id' => $org->id, 'provider' => 'todoist', 'credentials' => ['token' => 'token'], 'is_active' => true]);

        Http::fake([
            'api.todoist.com/api/v1/projects' => Http::response(['results' => [['id' => 'p-1', 'name' => 'Some Project']]]),
            'api.todoist.com/api/v1/projects/p-1/collaborators' => Http::response(['results' => [
                ['id' => 'uid-1', 'name' => 'Rosemary Muthui', 'email' => 'rosemary@example.com'],
            ]]),
            'api.todoist.com/api/v1/tasks' => Http::response(['results' => [
                ['id' => 'task-1', 'content' => 'Hers', 'description' => '', 'checked' => false, 'project_id' => 'p-1', 'responsible_uid' => 'uid-1'],
                ['id' => 'task-2', 'content' => 'Unassigned', 'description' => '', 'checked' => false, 'project_id' => 'p-1', 'responsible_uid' => null],
            ]]),
        ]);

        $response = $this->getJson('/api/todoist/tasks?organization=org&assignee=rosemary')->assertStatus(200);

        $response->assertJsonCount(1);
        $response->assertJsonFragment(['content' => 'Hers']);
    }
}
