<?php

namespace Prezet\Prezet\Actions;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Prezet\Prezet\Prezet;

class CreateIndex
{
    public function handle(): void
    {
        if (Prezet::isDedicatedSqliteStrategy()) {
            $this->handleSqliteStrategy();
        } else {
            $this->handleSharedDatabaseStrategy();
        }
    }

    /**
     * Handle SQLite strategy (original implementation - backward compatible).
     */
    protected function handleSqliteStrategy(): void
    {
        $originalPath = Config::string('database.connections.prezet.database');
        $tempPath = sys_get_temp_dir().'/prezet_'.uniqid().'.sqlite';

        try {
            Log::info('Creating new prezet SQLite index', [
                'strategy' => 'sqlite',
                'temp_path' => $tempPath,
                'target_path' => $originalPath
            ]);

            touch($tempPath);
            Config::set('database.connections.prezet.database', $tempPath);
            DB::purge('prezet');

            $this->runSqliteMigrations($tempPath);

            // Ensure the SQLite connection is properly closed to release locks
            DB::connection('prezet')->disconnect();

            $this->ensureDirectoryExists($originalPath);

            // Retry mechanism for handling file locks.
            $maxRetries = 5;
            $retryDelay = 200; // milliseconds
            for ($i = 0; $i < $maxRetries; $i++) {
                if (rename($tempPath, $originalPath)) {
                    break;
                }
                usleep($retryDelay * 1000);
            }

            if (! file_exists($originalPath)) {
                throw new \RuntimeException("Failed to move database from {$tempPath} to {$originalPath} after {$maxRetries} retries.");
            }

            Log::info('Successfully created new prezet SQLite index');
        } catch (\Exception $e) {
            Log::error('Failed to create prezet SQLite index', [
                'error' => $e->getMessage(),
                'temp_path' => $tempPath,
                'target_path' => $originalPath,
            ]);
            throw $e;
        } finally {
            Config::set('database.connections.prezet.database', $originalPath);
            DB::purge('prezet');

            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    /**
     * Handle shared database strategy (Laravel Cloud compatible).
     */
    protected function handleSharedDatabaseStrategy(): void
    {
        $connection = Prezet::getDatabaseConnection();
        $tablePrefix = Prezet::getTablePrefix();

        try {
            Log::info('Creating new prezet shared database index', [
                'strategy' => 'shared',
                'connection' => $connection ?? 'default',
                'table_prefix' => $tablePrefix
            ]);

            // Drop existing Prezet tables if they exist
            $this->dropExistingPrezetTables($connection);

            // Create tables manually (since migration timestamps cause issues)
            $this->createSharedDatabaseTables($connection);

            Log::info('Successfully created new prezet shared database index');
        } catch (\Exception $e) {
            Log::error('Failed to create prezet shared database index', [
                'error' => $e->getMessage(),
                'connection' => $connection ?? 'default',
                'table_prefix' => $tablePrefix,
            ]);
            throw $e;
        }
    }

    protected function ensureDirectoryExists(string $path): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            if (! mkdir($dir, 0755, true)) {
                throw new \RuntimeException("Failed to create directory: {$dir}");
            }
        }
    }

    /**
     * Run migrations for SQLite strategy.
     */
    protected function runSqliteMigrations(string $path): void
    {
        if (! Schema::connection('prezet')->hasTable('migrations')) {
            Schema::connection('prezet')->create('migrations', function ($table) {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
            });
        }

        $result = Artisan::call('migrate', [
            '--path' => base_path('vendor/prezet/prezet/database/migrations'),
            '--database' => 'prezet',
            '--realpath' => true,
            '--no-interaction' => true,
            '--force' => true,
        ]);

        if ($result !== 0) {
            throw new \RuntimeException('SQLite migration failed: '.Artisan::output());
        }
    }

    /**
     * Create tables directly for shared database strategy.
     */
    protected function createSharedDatabaseTables(?string $connection): void
    {
        $tablePrefix = Prezet::getTablePrefix();

        // Create documents table
        Schema::connection($connection)->create($tablePrefix . 'documents', function ($table) {
            $table->id();
            $table->string('key')->index()->nullable()->unique();
            $table->string('slug')->index()->unique();
            $table->string('filepath')->index()->unique();
            $table->string('category')->index()->nullable();
            $table->string('content_type')->index();
            $table->boolean('draft')->default(false)->index();
            $table->char('hash', 32)->index()->unique();
            $table->json('frontmatter');
            $table->timestampTz('created_at')->index();
            $table->timestampTz('updated_at')->index();
            $table->index(['filepath', 'hash']);
        });

        // Create tags table
        Schema::connection($connection)->create($tablePrefix . 'tags', function ($table) {
            $table->id();
            $table->string('name')->unique();
        });

        // Create headings table
        Schema::connection($connection)->create($tablePrefix . 'headings', function ($table) use ($tablePrefix) {
            $table->id();
            $table->foreignId('document_id')->constrained($tablePrefix . 'documents')->onDelete('cascade');
            $table->string('text');
            $table->unsignedTinyInteger('level');
            $table->string('section');
        });

        // Create document_tags pivot table
        Schema::connection($connection)->create($tablePrefix . 'document_tags', function ($table) use ($tablePrefix) {
            $table->id();
            $table->foreignId('document_id')->index()->constrained($tablePrefix . 'documents');
            $table->foreignId('tag_id')->index()->constrained($tablePrefix . 'tags');
        });

        Log::debug('Created all Prezet tables with prefix: ' . $tablePrefix);
    }

    /**
     * Drop existing Prezet tables for fresh creation.
     */
    protected function dropExistingPrezetTables(?string $connection): void
    {
        $tables = [
            Prezet::getTableName('document_tags'),
            Prezet::getTableName('headings'),
            Prezet::getTableName('documents'),
            Prezet::getTableName('tags'),
        ];

        foreach ($tables as $table) {
            if (Schema::connection($connection)->hasTable($table)) {
                Schema::connection($connection)->dropIfExists($table);
                Log::debug("Dropped existing table: {$table}");
            }
        }
    }
}
