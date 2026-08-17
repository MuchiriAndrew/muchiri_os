<?php

namespace App\Services;

use App\Models\Integration;
use App\Models\Organization;
use App\Models\Project;
use App\Models\WorkItem;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mirrors muchiri_os work items into Todoist as the human-facing task view.
 * muchiri_os stays the source of truth -- every method here is best-effort:
 * a Todoist failure is logged, never thrown, and never blocks the caller.
 *
 * Credentials are resolved per-Organization (via the work item's project),
 * not globally -- a personal work item must never sync using a work Todoist
 * token just because that's the only one configured. If an organization has
 * no Todoist integration connected, its work items simply don't sync.
 */
class TodoistService
{
    private const BASE_URL = 'https://api.todoist.com/api/v1';

    public function isConfiguredFor(Organization $organization): bool
    {
        return filled($this->tokenFor($organization));
    }

    /**
     * Lazily creates a Todoist project the first time a muchiri_os project
     * needs one, then remembers the mapping.
     */
    public function ensureProjectFor(Project $project): ?string
    {
        if ($project->todoist_project_id) {
            return $project->todoist_project_id;
        }

        $token = $this->tokenFor($project->organization);

        if (! $token) {
            return null;
        }

        $response = $this->client($token)->post(self::BASE_URL.'/projects', [
            'name' => $project->name,
        ]);

        if ($response->failed()) {
            Log::warning('Todoist: failed to create project', [
                'project' => $project->slug,
                'organization' => $project->organization->slug,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $todoistProjectId = $response->json('id');
        $project->update(['todoist_project_id' => $todoistProjectId]);
        $this->touchUsed($project->organization);

        return $todoistProjectId;
    }

    public function createTask(WorkItem $workItem): ?string
    {
        $organization = $workItem->project->organization;
        $token = $this->tokenFor($organization);

        if (! $token) {
            return null;
        }

        $projectId = $this->ensureProjectFor($workItem->project);

        if (! $projectId) {
            return null;
        }

        $response = $this->client($token)->post(self::BASE_URL.'/tasks', [
            'content' => $workItem->title,
            'description' => $this->describeFor($workItem),
            'project_id' => $projectId,
        ]);

        if ($response->failed()) {
            Log::warning('Todoist: failed to create task', [
                'work_item_id' => $workItem->id,
                'organization' => $organization->slug,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $this->touchUsed($organization);

        return $response->json('id');
    }

    /**
     * The plain REST `/tasks/{id}/close` action endpoint reliably returns a
     * 400 (error_code 42) on the current Unified API v1, even with a valid
     * task id and token -- confirmed by testing directly, not documented
     * anywhere. Closing only works through the Sync API's command-batch
     * endpoint, so that's what this uses.
     */
    public function completeTask(WorkItem $workItem): bool
    {
        $organization = $workItem->project->organization;
        $token = $this->tokenFor($organization);

        if (! $token || ! $workItem->todoist_task_id) {
            return false;
        }

        $response = $this->client($token)->post(self::BASE_URL.'/sync', [
            'commands' => [[
                'type' => 'item_close',
                'uuid' => (string) Str::uuid(),
                'args' => ['id' => $workItem->todoist_task_id],
            ]],
        ]);

        $ok = $response->successful() && collect($response->json('sync_status'))->every(fn ($status) => $status === 'ok');

        if (! $ok) {
            Log::warning('Todoist: failed to close task', [
                'task_id' => $workItem->todoist_task_id,
                'organization' => $organization->slug,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        $this->touchUsed($organization);

        return true;
    }

    /**
     * Todoist has no native "blocked" state, so a blocked work item stays
     * open with a comment explaining why, rather than being closed.
     */
    public function commentOnTask(WorkItem $workItem, string $content): bool
    {
        $organization = $workItem->project->organization;
        $token = $this->tokenFor($organization);

        if (! $token || ! $workItem->todoist_task_id) {
            return false;
        }

        $response = $this->client($token)->post(self::BASE_URL.'/comments', [
            'task_id' => $workItem->todoist_task_id,
            'content' => $content,
        ]);

        if ($response->failed()) {
            Log::warning('Todoist: failed to comment on task', [
                'task_id' => $workItem->todoist_task_id,
                'organization' => $organization->slug,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        $this->touchUsed($organization);

        return true;
    }

    private function tokenFor(Organization $organization): ?string
    {
        return Integration::forOrganization($organization, 'todoist')?->credential('token');
    }

    private function client(string $token): PendingRequest
    {
        return Http::withToken($token)->asJson()->timeout(10);
    }

    private function describeFor(WorkItem $workItem): string
    {
        return Str::limit($workItem->description, 3000)
            ."\n\n---\nAssigned: {$workItem->assigned_agent}\nCreated by: {$workItem->created_by}"
            ."\nmuchiri_os work item #{$workItem->id}";
    }

    private function touchUsed(Organization $organization): void
    {
        Integration::forOrganization($organization, 'todoist')?->touchLastUsed();
    }
}
