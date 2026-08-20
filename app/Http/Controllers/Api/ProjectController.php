<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\Organization;
use App\Models\Project;
use App\Services\TodoistService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
     * Register a project in muchiri_os -- either an existing repo Andrew describes
     * (pass `repository`) or a brand-new one an agent is about to scaffold (omit it;
     * Ulthane adds the repository record itself once the codebase actually exists).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('projects', 'slug')],
            'organization' => ['required', 'string', Rule::exists('organizations', 'slug')],
            'status' => ['nullable', Rule::in(['active', 'beta', 'paused', 'archived'])],
            'objective' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'created_by' => ['required', 'string', 'max:100'],
            'repository' => ['nullable', 'array'],
            'repository.name' => ['required_with:repository', 'string', 'max:255'],
            'repository.provider' => ['required_with:repository', 'string', 'max:50'],
            'repository.repository' => ['required_with:repository', 'string', 'max:255'],
            'repository.role' => ['nullable', 'string', 'max:50'],
            'repository.local_path' => ['required_with:repository', 'string', 'max:500'],
            'repository.default_branch' => ['nullable', 'string', 'max:100'],
        ]);

        $slug = $data['slug'] ?? Str::slug($data['name']);

        if (Project::where('slug', $slug)->exists()) {
            return response()->json([
                'message' => "A project with slug \"{$slug}\" already exists.",
            ], 422);
        }

        $organization = Organization::where('slug', $data['organization'])->firstOrFail();

        $project = $organization->projects()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'status' => $data['status'] ?? 'active',
            'objective' => $data['objective'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        if (! empty($data['repository'])) {
            $project->repositories()->create([
                'name' => $data['repository']['name'],
                'provider' => $data['repository']['provider'],
                'repository' => $data['repository']['repository'],
                'role' => $data['repository']['role'] ?? 'other',
                'local_path' => $data['repository']['local_path'],
                'default_branch' => $data['repository']['default_branch'] ?? 'main',
            ]);
        }

        AuditEvent::record(
            $data['created_by'],
            'project.created',
            $project,
            "Project \"{$project->name}\" registered in muchiri_os.",
        );

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'status' => $project->status,
                'objective' => $project->objective,
            ],
            'repositories' => $project->repositories()->get(['name', 'provider', 'repository', 'role', 'local_path', 'default_branch']),
        ], 201);
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

    public function todoistTasks(Project $project, \Illuminate\Http\Request $request)
    {
        return $this->todoist->listTasks($project, $request->query('assignee'));
    }

    /**
     * Attach a repository to a project after the fact -- e.g. once Ulthane finishes
     * scaffolding a brand-new project that was registered without one yet.
     */
    public function addRepository(Request $request, Project $project)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'string', 'max:50'],
            'repository' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:50'],
            'local_path' => ['required', 'string', 'max:500'],
            'default_branch' => ['nullable', 'string', 'max:100'],
            'created_by' => ['required', 'string', 'max:100'],
        ]);

        $repo = $project->repositories()->create([
            'name' => $data['name'],
            'provider' => $data['provider'],
            'repository' => $data['repository'],
            'role' => $data['role'] ?? 'other',
            'local_path' => $data['local_path'],
            'default_branch' => $data['default_branch'] ?? 'main',
        ]);

        AuditEvent::record(
            $data['created_by'],
            'project.repository_added',
            $project,
            "Repository \"{$repo->repository}\" attached to \"{$project->name}\".",
        );

        return response()->json($repo->only(['name', 'provider', 'repository', 'role', 'local_path', 'default_branch']), 201);
    }
}
