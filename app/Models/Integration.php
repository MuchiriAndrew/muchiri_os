<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Credential store for external services (Todoist, GitHub, etc.) -- the ECP
 * holds these encrypted at rest so no integration token needs to live in a
 * .env file, a script, or an agent-visible config. This model is only ever
 * used from trusted server-side code (services like TodoistService), never
 * exposed through the public /api/* surface agents talk to.
 *
 * Scoped per Organization -- there is no implicit global fallback. A project
 * whose organization has no configured integration simply doesn't sync,
 * rather than silently inheriting some other organization's credentials
 * (e.g. personal work items landing in a work Todoist account).
 */
#[Fillable(['organization_id', 'provider', 'label', 'credentials', 'config', 'is_active'])]
class Integration extends Model
{
    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'config' => 'array',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public static function forOrganization(Organization|int $organization, string $provider): ?self
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        return static::where('organization_id', $organizationId)
            ->where('provider', $provider)
            ->where('is_active', true)
            ->first();
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return data_get($this->credentials, $key, $default);
    }

    public function touchLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
