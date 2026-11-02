<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

/**
 * MongoDB collections are created implicitly; this migration only declares
 * their indexes (T08). createIndex is idempotent, so re-running it after
 * `migrate:fresh` (which only wipes MySQL) is safe.
 */
return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        Schema::connection('mongodb')->table('attempts', function (Blueprint $collection) {
            $collection->index('user_id');
            $collection->index('exam_id');
            $collection->index(['created_at' => -1]);
            $collection->index(['status' => 1, 'expires_at' => 1]);
        });

        Schema::connection('mongodb')->table('activity_logs', function (Blueprint $collection) {
            $collection->index('user_id');
            $collection->index('action');
            $collection->index(['created_at' => -1]);
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('attempts');
        Schema::connection('mongodb')->dropIfExists('activity_logs');
    }
};
