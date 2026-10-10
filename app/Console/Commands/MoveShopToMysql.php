<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class MoveShopToMysql extends Command
{
    protected $signature = 'shop:move-to-mysql {--database=shoe_commerce : A new, dedicated MySQL database}';

    protected $description = 'Back up SQLite, copy and verify its data, then switch this app to MySQL';

    public function handle(): int
    {
        $database = $this->option('database');
        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/', $database)) {
            $this->error('Use a database name containing only letters, digits, and underscores.');

            return self::FAILURE;
        }
        if (config('database.default') !== 'sqlite') {
            $this->error('The application must still be using SQLite before running this command.');

            return self::FAILURE;
        }
        if (config('database.connections.mysql.url')) {
            $this->error('Remove DB_URL and configure the individual MySQL settings first.');

            return self::FAILURE;
        }
        $source = config('database.connections.sqlite.database');
        if (! is_file($source)) {
            $this->error('The source SQLite database does not exist.');

            return self::FAILURE;
        }
        $wasDown = app()->isDownForMaintenance();
        $paused = false;
        try {
            // Test server access before changing the source or application configuration.
            config(['database.connections.mysql_server' => [
                ...config('database.connections.mysql'), 'database' => null,
            ]]);
            $server = DB::connection('mysql_server');
            $existing = $server->selectOne('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$database]);
            if ($existing) {
                throw new RuntimeException('Target database already exists. Use --database with a new name; existing databases are never overwritten.');
            }
            $server->statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            config(['database.connections.mysql.database' => $database]);
            DB::purge('mysql');
            if (! $wasDown) {
                $this->call('down');
                $paused = true;
            }
            $sqlite = DB::connection('sqlite');
            $backupDirectory = storage_path('app/private/database-backups');
            if (! is_dir($backupDirectory)) {
                mkdir($backupDirectory, 0700, true);
            }
            $backup = $backupDirectory.'/sqlite-before-mysql-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sqlite';
            $sqlite->getPdo()->exec('VACUUM INTO '.$sqlite->getPdo()->quote($backup));
            chmod($backup, 0600);
            $this->info('SQLite backup: '.$backup);
            if ($this->call('migrate', ['--database' => 'mysql', '--force' => true]) !== 0) {
                throw new RuntimeException('MySQL migrations failed. SQLite remains active.');
            }
            $tables = ['users', 'password_reset_tokens', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'products', 'variants', 'wholesale_accounts', 'orders', 'order_items', 'sessions'];
            $sourceTables = array_column($sqlite->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' AND name != 'migrations'"), 'name');
            if (array_diff($sourceTables, $tables)) {
                throw new RuntimeException('Unrecognized source tables found. Import aborted to avoid missing data.');
            }
            $tables = array_values(array_intersect($tables, $sourceTables));
            $target = DB::connection('mysql');
            $sqlite->beginTransaction();
            try {
                $target->transaction(function () use ($tables, $sqlite, $target) {
                    foreach ($tables as $table) {
                        $rows = $sqlite->table($table)->get()->map(fn ($row) => (array) $row)->all();
                        foreach (array_chunk($rows, 250) as $batch) {
                            $target->table($table)->insert($batch);
                        }
                        if ($target->table($table)->count() !== count($rows)) {
                            throw new RuntimeException('Row count verification failed for '.$table);
                        }
                        // Compare every column, normalizing numeric PDO representation differences.
                        $normalize = function (array $values): array {
                            return array_map(function ($row) {
                                $row = (array) $row;
                                ksort($row);

                                return array_map(fn ($value) => $value === null ? null : (string) $value, $row);
                            }, $values);
                        };
                        $original = $normalize($rows);
                        $copied = $normalize($target->table($table)->get()->all());
                        $sort = fn ($a, $b) => strcmp(json_encode($a), json_encode($b));
                        usort($original, $sort);
                        usort($copied, $sort);
                        if ($original !== $copied) {
                            throw new RuntimeException('Data verification failed for '.$table);
                        }
                        $this->line($table.': '.count($rows).' rows verified');
                    }
                });
            } finally {
                $sqlite->rollBack();
            }
            $environmentPath = base_path('.env');
            $environment = file_get_contents($environmentPath);
            foreach (['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $database] as $key => $value) {
                $pattern = '/^'.preg_quote($key, '/').'=.*/m';
                $environment = preg_match($pattern, $environment)
                    ? preg_replace($pattern, $key.'='.$value, $environment)
                    : $environment."\n".$key.'='.$value."\n";
            }
            $temporary = $environmentPath.'.mysql-switch';
            if (file_put_contents($temporary, $environment) === false) {
                throw new RuntimeException('Could not prepare the environment file. SQLite remains active.');
            }
            chmod($temporary, 0600);
            if (! rename($temporary, $environmentPath)) {
                throw new RuntimeException('Could not switch the environment file. SQLite remains active.');
            }
            $this->call('config:clear');
            $this->info('Data verified. The app now uses MySQL database '.$database.'. Original SQLite file preserved.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            $this->warn('Import did not finish. The original SQLite file is preserved. If a target database was created, inspect it before retrying with a new name.');

            return self::FAILURE;
        } finally {
            if ($paused) {
                $this->call('up');
            }
        }
    }
}
