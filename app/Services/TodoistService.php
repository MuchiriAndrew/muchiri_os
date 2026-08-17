<?php

namespace App\Services;

use App\Models\Integration;
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
 */
class TodoistService
{
    private const BASE_URL = 'https://api.todoist.com/api/v1';

    private ?string $token;

    public function __construct()
    {
        $this->token = Integration::forProvider('todoist')?->credential('token');
    }

    public function isConfigured(): bool
    {
        return filled($this->token);
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

        if (! $this->isConfigured()) {
            return null;
        }

        $response = $this->client()->post(self::BASE_URL.'/projects', [
            'name' => $project->name,
        ]);

        if ($response->failed()) {
            Log::warning('Todoist: failed to create project', [
                'project' => $project->slug,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $todoistProjectId = $response->json('id');
        $project->update(['todoist_project_id' => $todoistProjectId]);
        $this->touchUsed();

        return $todoistProjectId;
    }

    public function createTask(WorkItem $workItem): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $projectId = $this->ensureProjectFor($workItem->project);

        if (! $projectId) {
            return null;
        }

        $response = $this->client()->post(self::BASE_URL.'/tasks', [
            'content' => $workItem->title,
            'description' => $this->describeFor($workItem),
            'project_id' => $projectId,
        ]);

        if ($response->failed()) {
            Log::warning('Todoist: failed to create task', [
                'work_item_id' => $workItem->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $this->touchUsed();

        return $response->json('id');
    }

    public function completeTask(string $taskId): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $response = $this->client()->post(self::BASE_URL."/tasks/{$taskId}/close", []);

        if ($response->failed()) {
            Log::warning('Todoist: failed to close task', [
                'task_id' => $taskId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        $this->touchUsed();

        return true;
    }

    /**
     * Todoist has no native "blocked" state, so a blocked work item stays
     * open with a comment explaining why, rather than being closed.
     */
    public function commentOnTask(string $taskId, string $content): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $response = $this->client()->post(self::BASE_URL.'/comments', [
            'task_id' => $taskId,
            'content' => $content,
        ]);

        if ($response->failed()) {
            Log::warning('Todoist: failed to comment on task', [
                'task_id' => $taskId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        $this->touchUsed();

        return true;
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->token)->asJson()->timeout(10);
    }

    private function describeFor(WorkItem $workItem): string
    {
        return Str::limit($workItem->description, 3000)
            ."\n\n---\nAssigned: {$workItem->assigned_agent}\nCreated by: {$workItem->created_by}"
            ."\nmuchiri_os work item #{$workItem->id}";
    }

    private function touchUsed(): void
    {
        Integration::forProvider('todoist')?->touchLastUsed();
    }
}
