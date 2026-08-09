<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkItemApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_work_item_lifecycle(): void
    {
        $user = User::factory()->create();
        $org = Organization::create(['name' => 'Test', 'slug' => 'test', 'kind' => 'personal', 'priority_weight' => 1]);
        Project::create(['organization_id' => $org->id, 'name' => 'Test Project', 'slug' => 'test-project', 'status' => 'active']);

        $this->actingAs($user, 'sanctum');

        $create = $this->postJson('/api/work-items', [
            'project' => 'test-project',
            'title' => 'Do the thing',
            'description' => 'A detailed description of the thing.',
            'assigned_agent' => 'ulthane',
            'created_by' => 'valkyrie',
        ]);
        $create->assertStatus(201)->assertJsonPath('status', 'pending');
        $id = $create->json('id');

        $poll = $this->getJson('/api/work-items?assigned_agent=ulthane&status=pending');
        $poll->assertStatus(200)->assertJsonCount(1);

        $this->patchJson("/api/work-items/{$id}", ['status' => 'in_progress'])
            ->assertJsonPath('status', 'in_progress')
            ->assertJsonPath('started_at', fn ($v) => $v !== null);

        $this->patchJson("/api/work-items/{$id}", ['status' => 'done', 'result' => 'All done.'])
            ->assertJsonPath('status', 'done')
            ->assertJsonPath('result', 'All done.');

        $pollAfter = $this->getJson('/api/work-items?assigned_agent=ulthane&status=pending');
        $pollAfter->assertJsonCount(0);
    }
}
