<?php

namespace App\Models\Mongo;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Append-only audit trail: who did what, and when (T08, T15).
 * Only App\Listeners\RecordActivity writes to it (CODING_CONTRACT C6.1).
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $connection = 'mongodb';

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'meta',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'subject_id' => 'integer',
        ];
    }
}
