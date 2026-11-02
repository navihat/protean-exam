<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CheckConnectionsTest extends TestCase
{
    public function test_it_reports_ok_for_both_databases(): void
    {
        $this->artisan('app:check-connections')
            ->expectsOutputToContain('MySQL')
            ->expectsOutputToContain('MongoDB')
            ->doesntExpectOutputToContain('FAIL')
            ->assertSuccessful();
    }

    public function test_it_fails_when_mongodb_is_unreachable(): void
    {
        config([
            'database.connections.mongodb.dsn' => 'mongodb://127.0.0.1:1',
            'database.connections.mongodb.options.serverSelectionTimeoutMS' => 200,
        ]);
        DB::purge('mongodb');

        $this->artisan('app:check-connections')
            ->expectsOutputToContain('FAIL')
            ->assertFailed();
    }
}
