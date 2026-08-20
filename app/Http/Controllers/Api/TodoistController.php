<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\Organization;
use App\Services\TodoistService;
use Illuminate\Http\Request;

/**
 * Direct Todoist access, independent of muchiri_os project records. Agents
 * should be able to reach anything in Todoist whether or not it has been
 * registered as a muchiri_os project -- ProjectController::todoistTasks()
 * stays for the "I already know the muchiri_os project" case, this
 * controller is for everything else.
 */
class TodoistController extends Controller
{
    public function __construct(private TodoistService $todoist) {}

    /**
     * Every Todoist project across every connected organization (or just one,
     * via ?organization=slug), so agents can resolve a project by name without
     * it needing to exist in muchiri_os at all.
     */
    public function projects(Request $request)
    {
        $organizations = $this->resolveOrganizations($request->query('organization'));

        return $organizations->flatMap(
            fn (Organization $org) => collect($this->todoist->listProjectsFor($org))
                ->map(fn (array $p) => [...$p, 'organization' => $org->slug]),
        )->values();
    }

    /**
     * Tasks, scoped by any combination of organization / project (name or
     * Todoist id) / assignee. Omit project entirely for "everything open
     * across this account." Omit organization to search every connected one.
     */
    public function tasks(Request $request)
    {
        $organizations = $this->resolveOrganizations($request->query('organization'));
        $projectQuery = $request->query('project');
        $assignee = $request->query('assignee');

        if (! $projectQuery) {
            return $organizations->flatMap(
                fn (Organization $org) => collect($this->todoist->listAllOpenTasksFor($org, $assignee))
                    ->map(fn (array $t) => [...$t, 'organization' => $org->slug]),
            )->values();
        }

        $matches = collect();

        foreach ($organizations as $org) {
            $projects = collect($this->todoist->listProjectsFor($org));

            $match = $projects->first(fn ($p) => $p['id'] === $projectQuery)
                ?? $projects->first(fn ($p) => str_contains(strtolower($p['name']), strtolower($projectQuery)));

            if (! $match) {
                continue;
            }

            $tasks = $this->todoist->listTasksForProjectId($org, $match['id'], $assignee, $match['name']);
            $matches = $matches->merge(collect($tasks)->map(fn ($t) => [...$t, 'project' => $match['name'], 'organization' => $org->slug]));
        }

        return $matches->values();
    }

    private function resolveOrganizations(?string $organizationSlug): \Illuminate\Support\Collection
    {
        if ($organizationSlug) {
            return Organization::where('slug', $organizationSlug)->get();
        }

        $orgIds = Integration::where('provider', 'todoist')->where('is_active', true)->pluck('organization_id');

        return Organization::whereIn('id', $orgIds)->get();
    }
}
