<?php

namespace Tests\Feature;

use App\Models\AgentRun;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentRunApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_upsert_creates_and_dedupes_by_source_and_session_id(): void
    {
        $user = User::factory()->create();
        $org = Organization::create(['name' => 'Test', 'slug' => 'test', 'kind' => 'personal', 'priority_weight' => 1]);
        Project::create(['organization_id' => $org->id, 'name' => 'Test Project', 'slug' => 'test-project', 'status' => 'active']);

        $this->actingAs($user, 'sanctum');

        $payload = [
            'runs' => [
                [
                    'agent_id' => 'ulthane',
                    'source' => 'mac',
                    'session_id' => 'abc-123',
                    'project' => 'test-project',
                    'model' => 'kimi-k2.7-code:cloud',
                    'model_provider' => 'ollama',
                    'input_tokens' => 1200,
                    'output_tokens' => 80,
                    'total_tokens' => 1280,
                    'status' => 'ok',
                    'triggered_by' => 'cron',
                    'started_at' => now()->subMinute()->toIso8601String(),
                    'completed_at' => now()->toIso8601String(),
                ],
            ],
        ];

        $this->postJson('/api/agent-runs', $payload)
            ->assertStatus(201)
            ->assertJson(['upserted' => 1]);

        $this->assertDatabaseCount('agent_runs', 1);

        // Re-sync the same session with updated token counts -- should update, not duplicate.
        $payload['runs'][0]['input_tokens'] = 1500;
        $this->postJson('/api/agent-runs', $payload)->assertStatus(201);

        $this->assertDatabaseCount('agent_runs', 1);
        $this->assertSame(1500, AgentRun::first()->input_tokens);

        $list = $this->getJson('/api/agent-runs?agent_id=ulthane');
        $list->assertStatus(200)->assertJsonCount(1);

        $summary = $this->getJson('/api/agent-runs/summary');
        $summary->assertStatus(200)->assertJsonFragment(['agent_id' => 'ulthane', 'source' => 'mac', 'run_count' => 1]);
    }
}
