<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'agent_id', 'source', 'session_id', 'project_id', 'work_item_id',
    'model', 'model_provider', 'input_tokens', 'output_tokens', 'total_tokens',
    'duration_ms', 'status', 'triggered_by', 'started_at', 'completed_at',
])]
class AgentRun extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }
}
