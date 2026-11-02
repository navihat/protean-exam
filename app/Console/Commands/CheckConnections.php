<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CheckConnections extends Command
{
    protected $signature = 'app:check-connections';

    protected $description = 'Check that the application can reach MySQL and MongoDB';

    public function handle(): int
    {
        $rows = [
            $this->probe('MySQL', function () {
                $connection = DB::connection('mysql');
                $version = $connection->selectOne('select version() as version')->version;

                return "database `{$connection->getDatabaseName()}`, MySQL {$version}";
            }),
            $this->probe('MongoDB', function () {
                $database = DB::connection('mongodb')->getDatabase();
                $database->command(['ping' => 1]);
                $version = $database->command(['buildInfo' => 1])->toArray()[0]['version'];

                return "database `{$database->getDatabaseName()}`, MongoDB {$version}";
            }),
        ];

        $this->table(['Connection', 'Status', 'Details'], $rows);

        return collect($rows)->every(fn (array $row) => $row[1] === 'OK')
            ? self::SUCCESS
            : self::FAILURE;
    }

    /**
     * @param  callable(): string  $check
     * @return array{string, string, string}
     */
    private function probe(string $name, callable $check): array
    {
        try {
            return [$name, 'OK', $check()];
        } catch (Throwable $e) {
            return [$name, 'FAIL', Str::limit($e->getMessage(), 120)];
        }
    }
}
