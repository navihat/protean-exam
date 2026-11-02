<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * MongoDB counterpart of RefreshDatabase: empties every collection of the
 * test database before each test. Documents are deleted rather than the
 * collections dropped, so indexes created by migrations are kept.
 */
trait RefreshMongo
{
    protected function setUpRefreshMongo(): void
    {
        $database = DB::connection('mongodb')->getDatabase();

        if (! str_ends_with($database->getDatabaseName(), '_testing')) {
            throw new RuntimeException('RefreshMongo refuses to wipe a non *_testing database.');
        }

        foreach ($database->listCollectionNames() as $collection) {
            $database->selectCollection($collection)->deleteMany([]);
        }
    }
}
