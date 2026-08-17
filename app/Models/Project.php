<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['organization_id', 'name', 'slug', 'status', 'objective', 'description', 'business_goals', 'todoist_project_id'])]
class Project extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class);
    }

    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    public function knowledgeItems(): HasMany
    {
        return $this->hasMany(KnowledgeItem::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class);
    }

    public function workItems(): HasMany
    {
        return $this->hasMany(WorkItem::class);
    }

    public function agentRuns(): HasMany
    {
        return $this->hasMany(AgentRun::class);
    }
}
