<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentRun;
use App\Models\Project;
use App\Models\WorkItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgentRunController extends Controller
{
    private const AGENTS = ['ulthane', 'valkyrie', 'samael', 'raziel', 'morrigan', 'thoth', 'argus'];

    private const SOURCES = ['mac', 'vps'];

    /**
     * List runs for reporting — supports filtering by agent, source, project, and date range.
     */
    public function index(Request $request)
    {
        $query = AgentRun::query()->orderByDesc('started_at');

        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->string('agent_id'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->string('source'));
        }

        if ($request->filled('project')) {
            $query->whereHas('project', fn ($q) => $q->where('slug', $request->string('project')));
        }

        if ($request->filled('since')) {
            $query->where('started_at', '>=', $request->date('since'));
        }

        return $query->limit(500)->get()->map(fn (AgentRun $run) => $this->present($run));
    }

    /**
     * Bulk upsert — the periodic sync script (Mac and VPS each run their own) pushes
     * a batch of runs read from local OpenClaw session data. Idempotent on
     * (source, session_id) so re-syncing the same session just updates it.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'runs' => ['required', 'array', 'min:1'],
            'runs.*.agent_id' => ['required', Rule::in(self::AGENTS)],
            'runs.*.source' => ['required', Rule::in(self::SOURCES)],
            'runs.*.session_id' => ['required', 'string'],
            'runs.*.project' => ['sometimes', 'nullable', 'string', Rule::exists('projects', 'slug')],
            'runs.*.work_item_id' => ['sometimes', 'nullable', 'integer', Rule::exists('work_items', 'id')],
            'runs.*.model' => ['sometimes', 'nullable', 'string'],
            'runs.*.model_provider' => ['sometimes', 'nullable', 'string'],
            'runs.*.input_tokens' => ['sometimes', 'integer', 'min:0'],
            'runs.*.output_tokens' => ['sometimes', 'integer', 'min:0'],
            'runs.*.total_tokens' => ['sometimes', 'integer', 'min:0'],
            'runs.*.duration_ms' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'runs.*.status' => ['sometimes', Rule::in(['ok', 'error', 'timeout', 'unknown'])],
            'runs.*.triggered_by' => ['sometimes', 'nullable', 'string', 'max:50'],
            'runs.*.started_at' => ['sometimes', 'nullable', 'date'],
            'runs.*.completed_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $upserted = 0;

        foreach ($data['runs'] as $row) {
            $projectId = null;
            if (! empty($row['project'])) {
                $projectId = Project::where('slug', $row['project'])->value('id');
            }

            AgentRun::updateOrCreate(
                ['source' => $row['source'], 'session_id' => $row['session_id']],
                [
                    'agent_id' => $row['agent_id'],
                    'project_id' => $projectId,
                    'work_item_id' => $row['work_item_id'] ?? null,
                    'model' => $row['model'] ?? null,
                    'model_provider' => $row['model_provider'] ?? null,
                    'input_tokens' => $row['input_tokens'] ?? 0,
                    'output_tokens' => $row['output_tokens'] ?? 0,
                    'total_tokens' => $row['total_tokens'] ?? 0,
                    'duration_ms' => $row['duration_ms'] ?? null,
                    'status' => $row['status'] ?? 'unknown',
                    'triggered_by' => $row['triggered_by'] ?? null,
                    'started_at' => $row['started_at'] ?? null,
                    'completed_at' => $row['completed_at'] ?? null,
                ],
            );

            $upserted++;
        }

        return response()->json(['upserted' => $upserted], 201);
    }

    /**
     * Simple aggregate summary — tokens/runs grouped by agent, for a quick usage view.
     */
    public function summary(Request $request)
    {
        $query = AgentRun::query();

        if ($request->filled('since')) {
            $query->where('started_at', '>=', $request->date('since'));
        }

        $rows = $query->selectRaw('agent_id, source, count(*) as run_count, sum(input_tokens) as input_tokens, sum(output_tokens) as output_tokens, sum(total_tokens) as total_tokens')
            ->groupBy('agent_id', 'source')
            ->orderBy('agent_id')
            ->get();

        return $rows;
    }

    private function present(AgentRun $run): array
    {
        return [
            'id' => $run->id,
            'agent_id' => $run->agent_id,
            'source' => $run->source,
            'session_id' => $run->session_id,
            'project' => $run->project?->slug,
            'work_item_id' => $run->work_item_id,
            'model' => $run->model,
            'model_provider' => $run->model_provider,
            'input_tokens' => $run->input_tokens,
            'output_tokens' => $run->output_tokens,
            'total_tokens' => $run->total_tokens,
            'duration_ms' => $run->duration_ms,
            'status' => $run->status,
            'triggered_by' => $run->triggered_by,
            'started_at' => $run->started_at?->toIso8601String(),
            'completed_at' => $run->completed_at?->toIso8601String(),
        ];
    }
}
