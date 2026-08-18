<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Reads always hit the database directly, uncached. This table is tiny and
 * read rarely enough that a cache isn't worth the risk: settings like
 * active_machine gate whether an entire machine's agents are allowed to act,
 * and a stale cached read here previously let a machine fire when it
 * shouldn't have -- worse than the extra query.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
