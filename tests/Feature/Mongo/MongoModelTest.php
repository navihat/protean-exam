<?php

namespace Tests\Feature\Mongo;

use App\Models\Mongo\ActivityLog;
use Tests\Concerns\RefreshMongo;
use Tests\TestCase;

class MongoModelTest extends TestCase
{
    use RefreshMongo;

    public function test_mongo_model_can_write_and_read_a_document(): void
    {
        $log = ActivityLog::create([
            'user_id' => '7',
            'action' => 'exam.created',
            'meta' => ['exam_name' => 'Sample exam'],
        ]);

        $found = ActivityLog::find($log->id);

        $this->assertNotNull($found);
        $this->assertSame(7, $found->user_id);
        $this->assertSame('exam.created', $found->action);
        $this->assertSame('Sample exam', $found->meta['exam_name']);
        $this->assertNotNull($found->created_at);
    }

    public function test_refresh_mongo_starts_each_test_with_empty_collections(): void
    {
        $this->assertSame(0, ActivityLog::count());
    }
}
