<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['project_id', 'actor', 'action', 'description', 'metadata', 'result'])]
class AuditEvent extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(string $actor, string $action, ?Project $project = null, ?string $description = null, array $metadata = [], string $result = 'success'): self
    {
        return static::create([
            'project_id' => $project?->id,
            'actor' => $actor,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'result' => $result,
        ]);
    }
}
