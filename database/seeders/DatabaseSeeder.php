<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * All seeders are idempotent: the Docker entrypoint runs `db:seed` on every start.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ExamSeeder::class,
        ]);
    }
}
