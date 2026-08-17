<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\Project;
use App\Models\WorkItem;
use App\Services\TodoistService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkItemController extends Controller
{
    private const AGENTS = ['ulthane', 'valkyrie', 'samael', 'raziel', 'morrigan', 'thoth', 'argus'];

    public function __construct(private readonly TodoistService $todoist) {}

    /**
     * List work items — the polling primitive agents use to find their own pending work.
     * e.g. GET /api/work-items?assigned_agent=ulthane&status=pending
     */
    public function index(Request $request)
    {
        $query = WorkItem::with('project:id,name,slug')
            ->orderBy('created_at');

        if ($request->filled('assigned_agent')) {
            $query->where('assigned_agent', $request->string('assigned_agent'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('project')) {
            $query->whereHas('project', fn ($q) => $q->where('slug', $request->string('project')));
        }

        return $query->get()->map(fn (WorkItem $item) => $this->present($item));
    }

    public function show(WorkItem $workItem)
    {
        return $this->present($workItem->load('project:id,name,slug'));
    }

    /**
     * Create a work item — this is how Valkyrie hands off a task without ever
     * talking to the target agent directly. The target agent finds it by polling.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'project' => ['required', 'string', Rule::exists('projects', 'slug')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'assigned_agent' => ['required', Rule::in(self::AGENTS)],
            'created_by' => ['required', 'string', 'max:100'],
        ]);

        $project = Project::where('slug', $data['project'])->firstOrFail();

        $workItem = $project->workItems()->create([
            'title' => $data['title'],
            'description' => $data['description'],
            'assigned_agent' => $data['assigned_agent'],
            'created_by' => $data['created_by'],
            'status' => 'pending',
        ]);

        AuditEvent::record(
            $data['created_by'],
            'work_item.created',
            $project,
            "Work item \"{$workItem->title}\" assigned to {$workItem->assigned_agent}.",
        );

        if ($todoistTaskId = $this->todoist->createTask($workItem)) {
            $workItem->update(['todoist_task_id' => $todoistTaskId]);
        }

        return response()->json($this->present($workItem->fresh()->load('project:id,name,slug')), 201);
    }

    /**
     * Update status/result — how an agent reports back when it picks up or finishes work.
     */
    public function update(Request $request, WorkItem $workItem)
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(['pending', 'in_progress', 'done', 'blocked'])],
            'result' => ['sometimes', 'nullable', 'string'],
        ]);

        if (($data['status'] ?? null) === 'in_progress') {
            $data['started_at'] = now();

            // Two pollers can race to claim the same pending item; only the first
            // conditional update wins, everyone else gets a 409.
            $claimed = WorkItem::where('id', $workItem->id)
                ->where('status', 'pending')
                ->update($data);

            if ($claimed === 0) {
                return response()->json([
                    'message' => "Work item already claimed (current status: {$workItem->fresh()->status}).",
                ], 409);
            }

            $workItem = $workItem->fresh();
            $this->syncTodoistStatus($workItem);

            return $this->present($workItem->load('project:id,name,slug'));
        }

        if (($data['status'] ?? null) === 'done' && $workItem->status !== 'done') {
            $data['completed_at'] = now();
        }

        $workItem->update($data);

        if (isset($data['status'])) {
            AuditEvent::record(
                $workItem->assigned_agent,
                'work_item.status_changed',
                $workItem->project,
                "Work item \"{$workItem->title}\" -> {$workItem->status}.",
                ['work_item_id' => $workItem->id],
            );

            $this->syncTodoistStatus($workItem);
        }

        return $this->present($workItem->fresh()->load('project:id,name,slug'));
    }

    private function syncTodoistStatus(WorkItem $workItem): void
    {
        if (! $workItem->todoist_task_id) {
            return;
        }

        match ($workItem->status) {
            'done' => $this->todoist->completeTask($workItem->todoist_task_id),
            'blocked' => $this->todoist->commentOnTask(
                $workItem->todoist_task_id,
                "Blocked: {$workItem->result}",
            ),
            'in_progress' => $this->todoist->commentOnTask(
                $workItem->todoist_task_id,
                "{$workItem->assigned_agent} started working on this.",
            ),
            default => null,
        };
    }

    private function present(WorkItem $item): array
    {
        return [
            'id' => $item->id,
            'project' => $item->project->slug,
            'title' => $item->title,
            'description' => $item->description,
            'assigned_agent' => $item->assigned_agent,
            'status' => $item->status,
            'created_by' => $item->created_by,
            'result' => $item->result,
            'todoist_task_id' => $item->todoist_task_id,
            'started_at' => $item->started_at?->toIso8601String(),
            'completed_at' => $item->completed_at?->toIso8601String(),
            'created_at' => $item->created_at->toIso8601String(),
        ];
    }
}
