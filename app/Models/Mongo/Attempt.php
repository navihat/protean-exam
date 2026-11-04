<?php

namespace App\Models\Mongo;

use App\Enums\AttemptStatus;
use MongoDB\Laravel\Eloquent\Model;

/**
 * One student's run through one exam (T08, T11, T12).
 *
 * `snapshot` is a shuffled copy of the questions and options, including is_correct,
 * taken when the attempt starts. Grading and results read only the snapshot (D5),
 * so later edits to the exam do not change past attempts.
 */
class Attempt extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'attempts';

    protected $fillable = [
        'user_id',
        'exam_id',
        'status',
        'exam',
        'snapshot',
        'started_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'exam_id' => 'integer',
            'status' => AttemptStatus::class,
            'correct_count' => 'integer',
            'total_questions' => 'integer',
            'score' => 'float',
            'is_late' => 'boolean',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }
}
