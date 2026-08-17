<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TodoistSyncTest extends TestCase
{
    use RefreshDatabase;

    private ?Organization $org = null;

    private function makeProject(string $orgSlug = 'test-org'): Project
    {
        $this->org = Organization::create(['name' => 'Test Org', 'slug' => $orgSlug, 'kind' => 'personal', 'priority_weight' => 1]);

        return Project::create(['organization_id' => $this->org->id, 'name' => 'Test Project', 'slug' => 'test-project', 'status' => 'active']);
    }

    public function test_work_item_creation_succeeds_even_without_todoist_configured(): void
    {
        $user = User::factory()->create();
        $this->makeProject();
        $this->actingAs($user, 'sanctum');

        Http::fake(); // no Todoist integration exists -- nothing should even be called

        $response = $this->postJson('/api/work-items', [
            'project' => 'test-project',
            'title' => 'Do the thing',
            'description' => 'A detailed description.',
            'assigned_agent' => 'ulthane',
            'created_by' => 'valkyrie',
        ]);

        $response->assertStatus(201)->assertJsonPath('todoist_task_id', null);
        Http::assertNothingSent();
    }

    public function test_work_item_creation_mirrors_into_todoist_when_configured(): void
    {
        $user = User::factory()->create();
        $this->makeProject();
        $this->actingAs($user, 'sanctum');

        Integration::create(['organization_id' => $this->org->id, 'provider' => 'todoist', 'credentials' => ['token' => 'fake-token'], 'is_active' => true]);

        Http::fake([
            'api.todoist.com/api/v1/projects' => Http::response(['id' => 'todoist-project-1'], 200),
            'api.todoist.com/api/v1/tasks' => Http::response(['id' => 'todoist-task-1'], 200),
        ]);

        $response = $this->postJson('/api/work-items', [
            'project' => 'test-project',
            'title' => 'Do the thing',
            'description' => 'A detailed description.',
            'assigned_agent' => 'ulthane',
            'created_by' => 'valkyrie',
        ]);

        $response->assertStatus(201)->assertJsonPath('todoist_task_id', 'todoist-task-1');

        $this->assertSame('todoist-project-1', Project::where('slug', 'test-project')->value('todoist_project_id'));

        Http::assertSent(fn ($request) => $request->url() === 'https://api.todoist.com/api/v1/projects' && $request['name'] === 'Test Project');
        Http::assertSent(fn ($request) => $request->url() === 'https://api.todoist.com/api/v1/tasks' && $request['project_id'] === 'todoist-project-1');
    }

    public function test_work_item_never_syncs_using_a_different_organizations_todoist_token(): void
    {
        $user = User::factory()->create();
        $this->makeProject('org-without-integration');
        $this->actingAs($user, 'sanctum');

        // A completely different organization has Todoist configured -- this
        // must never be used for work items belonging to $this->org.
        $otherOrg = Organization::create(['name' => 'Other Org', 'slug' => 'other-org', 'kind' => 'employer', 'priority_weight' => 1]);
        Integration::create(['organization_id' => $otherOrg->id, 'provider' => 'todoist', 'credentials' => ['token' => 'other-orgs-token'], 'is_active' => true]);

        Http::fake();

        $response = $this->postJson('/api/work-items', [
            'project' => 'test-project',
            'title' => 'Do the thing',
            'description' => 'A detailed description.',
            'assigned_agent' => 'ulthane',
            'created_by' => 'valkyrie',
        ]);

        $response->assertStatus(201)->assertJsonPath('todoist_task_id', null);
        Http::assertNothingSent();
    }

    public function test_marking_done_closes_the_todoist_task(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProject();
        $this->actingAs($user, 'sanctum');

        Integration::create(['organization_id' => $this->org->id, 'provider' => 'todoist', 'credentials' => ['token' => 'fake-token'], 'is_active' => true]);

        $workItem = $project->workItems()->create([
            'title' => 'Do the thing',
            'description' => 'desc',
            'assigned_agent' => 'ulthane',
            'created_by' => 'valkyrie',
            'status' => 'in_progress',
            'todoist_task_id' => 'todoist-task-1',
        ]);

        Http::fake([
            'api.todoist.com/api/v1/sync' => Http::response(['sync_status' => ['*' => 'ok']], 200),
        ]);

        $this->patchJson("/api/work-items/{$workItem->id}", ['status' => 'done', 'result' => 'Finished.'])
            ->assertStatus(200);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.todoist.com/api/v1/sync'
            && $request['commands'][0]['type'] === 'item_close'
            && $request['commands'][0]['args']['id'] === 'todoist-task-1');
    }

    public function test_marking_blocked_comments_instead_of_closing(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProject();
        $this->actingAs($user, 'sanctum');

        Integration::create(['organization_id' => $this->org->id, 'provider' => 'todoist', 'credentials' => ['token' => 'fake-token'], 'is_active' => true]);

        $workItem = $project->workItems()->create([
            'title' => 'Do the thing',
            'description' => 'desc',
            'assigned_agent' => 'ulthane',
            'created_by' => 'valkyrie',
            'status' => 'in_progress',
            'todoist_task_id' => 'todoist-task-1',
        ]);

        Http::fake([
            'api.todoist.com/api/v1/comments' => Http::response([], 200),
        ]);

        $this->patchJson("/api/work-items/{$workItem->id}", ['status' => 'blocked', 'result' => 'Missing access.'])
            ->assertStatus(200);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.todoist.com/api/v1/comments'
            && str_contains($request['content'], 'Missing access.'));

        // The task must NOT have been closed -- blocked stays open.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/close'));
    }

    public function test_listing_tasks_returns_empty_when_project_has_no_todoist_project_yet(): void
    {
        $user = User::factory()->create();
        $this->makeProject();
        $this->actingAs($user, 'sanctum');

        Integration::create(['organization_id' => $this->org->id, 'provider' => 'todoist', 'credentials' => ['token' => 'fake-token'], 'is_active' => true]);

        Http::fake(); // no todoist_project_id yet -- must not even call out

        $this->getJson('/api/projects/test-project/todoist-tasks')
            ->assertStatus(200)
            ->assertJson([]);

        Http::assertNothingSent();
    }

    public function test_listing_tasks_returns_the_projects_open_tasks(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProject();
        $this->actingAs($user, 'sanctum');

        Integration::create(['organization_id' => $this->org->id, 'provider' => 'todoist', 'credentials' => ['token' => 'fake-token'], 'is_active' => true]);
        $project->update(['todoist_project_id' => 'todoist-project-1']);

        Http::fake([
            'api.todoist.com/api/v1/tasks*' => Http::response([
                'results' => [
                    ['id' => 'task-1', 'content' => 'Do the thing', 'description' => 'desc', 'checked' => false, 'project_id' => 'todoist-project-1'],
                ],
                'next_cursor' => null,
            ], 200),
        ]);

        $response = $this->getJson('/api/projects/test-project/todoist-tasks')->assertStatus(200);

        $response->assertJson([
            [
                'id' => 'task-1',
                'content' => 'Do the thing',
                'description' => 'desc',
                'is_completed' => false,
                'url' => 'https://app.todoist.com/app/task/task-1',
            ],
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.todoist.com/api/v1/tasks?project_id=todoist-project-1');
    }
}
