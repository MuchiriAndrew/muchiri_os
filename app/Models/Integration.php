<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Credential store for external services (Todoist, GitHub, etc.) -- the ECP
 * holds these encrypted at rest so no integration token needs to live in a
 * .env file, a script, or an agent-visible config. This model is only ever
 * used from trusted server-side code (services like TodoistService), never
 * exposed through the public /api/* surface agents talk to.
 */
#[Fillable(['provider', 'label', 'credentials', 'config', 'is_active'])]
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

    public static function forProvider(string $provider): ?self
    {
        return static::where('provider', $provider)->where('is_active', true)->first();
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
