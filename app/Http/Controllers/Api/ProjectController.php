<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\TodoistService;

class ProjectController extends Controller
{
    public function __construct(private TodoistService $todoist) {}

    public function index()
    {
        return Project::select('id', 'name', 'slug', 'status', 'objective')
            ->orderBy('name')
            ->get();
    }

    /**
     * The ECP's core primitive: get_project_context().
     * Everything an agent needs to know before touching this project.
     */
    public function context(Project $project)
    {
        $project->load(['repositories', 'environments', 'decisions', 'knowledgeItems']);

        return response()->json([
            'project' => [
                'name' => $project->name,
                'slug' => $project->slug,
                'status' => $project->status,
                'objective' => $project->objective,
                'description' => $project->description,
                'business_goals' => $project->business_goals,
            ],
            'repositories' => $project->repositories->map(fn ($r) => [
                'name' => $r->name,
                'provider' => $r->provider,
                'repository' => $r->repository,
                'role' => $r->role,
                'local_path' => $r->local_path,
                'default_branch' => $r->default_branch,
            ]),
            'environments' => $project->environments->map(fn ($e) => [
                'name' => $e->name,
                'domain' => $e->domain,
                'server' => $e->server,
                'notes' => $e->notes,
            ]),
            'decisions' => $project->decisions->map(fn ($d) => [
                'title' => $d->title,
                'context' => $d->context,
                'decision' => $d->decision,
                'rationale' => $d->rationale,
                'decided_at' => $d->decided_at?->toDateString(),
            ]),
            'knowledge' => $project->knowledgeItems->map(fn ($k) => [
                'type' => $k->type,
                'title' => $k->title,
                'body' => $k->body,
            ]),
        ]);
    }

    public function todoistTasks(Project $project)
    {
        return $this->todoist->listTasks($project);
    }
}
