<?php

namespace Prezet\Prezet\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Prezet\Prezet\Prezet;

class InstallCommand extends Command
{
    use RunsCommands;

    public $signature = 'prezet:install {--force : Force the operation without confirmation} {--tailwind3 : Install Tailwind CSS v3 instead of v4}';

    public $description = 'Installs the Prezet package';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        // Skip all checks if force flag is set
        if ($this->option('force')) {
            return $this->runInstall();
        }

        try {
            $gitStatus = $this->checkGitStatus();

            // If git repo is dirty, exit with error
            if ($gitStatus === 'dirty') {
                $this->error('Git directory is not clean. Please stash or commit your changes before installing.');

                return self::FAILURE;
            }

            // If no git repo or clean repo, proceed with appropriate confirmation
            if ($gitStatus === 'no_git' && ! $this->confirm('No git repository detected. This will modify your project files. Do you wish to continue?')) {
                return self::FAILURE;
            }

            // If we get here, either the repo is clean or user confirmed to proceed
            return $this->runInstall();

        } catch (\Exception $e) {
            $this->error('An error occurred while checking git status: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function checkGitStatus(): string
    {
        try {
            $process = Process::run('git status --porcelain');

            if ($process->exitCode() !== 0) {
                // Not a git repository
                return 'no_git';
            }

            $output = $process->output();

            // If no changes, return clean
            if ($output === '') {
                return 'clean';
            }

            // Check if only composer files are modified
            $changes = array_filter(explode("\n", trim($output)));
            $onlyComposerFiles = true;

            foreach ($changes as $change) {
                if (! str_contains($change, 'composer')) {
                    $onlyComposerFiles = false;
                    break;
                }
            }

            return $onlyComposerFiles ? 'clean' : 'dirty';
        } catch (\Exception $e) {
            // If process fails for any reason, assume no git
            return 'no_git';
        }
    }

    protected function publishConfig(): void
    {
        $this->info('Publishing vendor files');
        $this->runCommands(['php artisan vendor:publish --provider="Prezet\Prezet\PrezetServiceProvider" --tag=prezet-config']);
    }

    protected function runInstall(): int
    {
        try {
            $this->addStorageDisk();
            $this->publishConfig();
            $this->setupDatabase();

            // run in separate process so config changes above are applied
            Process::run('php artisan prezet:index --fresh');
            $this->info('Prezet has been successfully installed!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('An error occurred during installation: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function setupDatabase(): void
    {
        // Check for existing Prezet installation
        $hasExistingPrezet = config('database.connections.prezet') !== null;
        $strategy = env('PREZET_DB_STRATEGY', config('prezet.database.strategy', 'sqlite'));
        
        // If no strategy specified in env, ask the user
        if (!env('PREZET_DB_STRATEGY')) {
            $this->info('');
            $this->info('🗃️  Database Strategy Configuration');
            $this->info('Prezet needs to store indexed content metadata in a database.');
            $this->info('');
            
            if ($hasExistingPrezet) {
                $this->warn('⚠️  Existing SQLite Prezet installation detected.');
                $this->info('You can continue with SQLite or migrate to shared database.');
                $this->info('');
            }
            
            // Auto-detect Laravel Cloud environment
            if ($this->isLaravelCloudEnvironment()) {
                $this->error('🌩️  Laravel Cloud environment detected!');
                $this->warn('SQLite files don\'t persist on Laravel Cloud due to ephemeral filesystem.');
                $this->info('Shared database strategy is recommended for cloud deployment.');
                $this->info('');
            }
            
            $this->info('Available strategies:');
            $this->info('1. SQLite (default) - Uses dedicated SQLite database file');
            $this->info('   ✅ Fast performance, zero configuration');
            $this->info('   ✅ Perfect for traditional hosting, VPS, local development');
            $this->info('   ❌ Not suitable for Laravel Cloud or containerized deployments');
            $this->info('');
            $this->info('2. Shared Database - Uses your Laravel app\'s main database');
            $this->info('   ✅ Compatible with Laravel Cloud and all cloud platforms');
            $this->info('   ✅ Works with MySQL, PostgreSQL, and all Laravel-supported databases');
            $this->info('   ✅ Tables are prefixed (prezet_) to avoid conflicts');
            $this->info('   ⚠️  Slightly more database overhead');
            $this->info('');
            
            $defaultChoice = $this->isLaravelCloudEnvironment() ? 'shared' : 'sqlite';
            $strategy = $this->choice(
                'Which database strategy would you like to use?',
                ['sqlite', 'shared'],
                $defaultChoice
            );
        }
        
        $this->info("Setting up Prezet with {$strategy} database strategy...");
        
        if ($strategy === 'sqlite') {
            $this->setupSqliteDatabase();
        } else {
            $this->setupSharedDatabase();
        }
    }

    protected function isLaravelCloudEnvironment(): bool
    {
        return env('LARAVEL_CLOUD') !== null ||
               str_contains(env('APP_URL', ''), '.laravel.app') ||
               env('SERVER_SOFTWARE') === 'Laravel Cloud';
    }

    protected function setupSqliteDatabase(): void
    {
        if (config('database.connections.prezet')) {
            $this->warn('Skipping SQLite database setup: the prezet database connection already exists.');
            return;
        }

        $this->info('Setting up SQLite database for Prezet');
        $configFile = config_path('database.php');
        $config = file_get_contents($configFile);
        if (! $config) {
            $this->error('Failed to read database config file: '.$configFile);
            return;
        }

        $diskConfig = "\n        'prezet' => [\n            'driver' => 'sqlite',\n            'database' => base_path('database/prezet.sqlite'),\n            'prefix' => '',\n            'foreign_key_constraints' => true,\n        ],";

        $disksPosition = strpos($config, "'connections' => [");
        if ($disksPosition !== false) {
            $disksPosition += strlen("'connections' => [");
            $newConfig = substr_replace($config, $diskConfig, $disksPosition, 0);
            file_put_contents($configFile, $newConfig);
        }
    }

    protected function setupSharedDatabase(): void
    {
        $connection = config('prezet.database.connection');
        $tablePrefix = config('prezet.database.table_prefix', 'prezet_');
        
        $this->info('Setting up shared database for Prezet');
        $this->info("Using connection: " . ($connection ?? 'default'));
        $this->info("Table prefix: {$tablePrefix}");
        $this->info('');
        
        // Verify database connection works
        try {
            DB::connection($connection)->getPdo();
            $this->info('✅ Database connection verified');
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: ' . $e->getMessage());
            throw new \Exception('Database connection failed. Please check your database configuration.');
        }
        
        // Create tables by running migrations
        $this->info('Creating Prezet tables...');
        
        try {
            // Use manual table creation for better reliability
            $this->createTablesManually($connection);
            $this->info('✅ Prezet tables created successfully');
            
            // Verify tables were created
            $requiredTables = [
                $tablePrefix . 'documents',
                $tablePrefix . 'tags', 
                $tablePrefix . 'headings',
                $tablePrefix . 'document_tags'
            ];
            
            foreach ($requiredTables as $table) {
                if (!Schema::connection($connection)->hasTable($table)) {
                    $this->error("❌ Missing table: {$table}");
                    throw new \Exception("Failed to create table: {$table}");
                } else {
                    $this->info("✅ Verified table: {$table}");
                }
            }
            
            $this->info('✅ All Prezet tables verified');
            $this->info('Shared database setup completed successfully!');
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to create Prezet tables: ' . $e->getMessage());
            throw $e;
        }
    }
    
    protected function runSharedDatabaseMigrations(?string $connection): void
    {
        // Use Artisan migrate command with the Prezet migrations
        $databaseOption = $connection ? ['--database' => $connection] : [];

        $result = Artisan::call('migrate', array_merge([
            '--path' => 'vendor/prezet/prezet/database/migrations',
            '--realpath' => true,
            '--no-interaction' => true,
            '--force' => true,
        ], $databaseOption));

        if ($result !== 0) {
            // If migrations fail, create tables manually
            $this->warn('Standard migrations failed, creating tables manually...');
            $this->createTablesManually($connection);
        }
    }
    
    protected function createTablesManually(?string $connection): void
    {
        $tablePrefix = config('prezet.database.table_prefix', 'prezet_');
        
        // Create documents table
        $this->info("Creating table: {$tablePrefix}documents");
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
        $this->info("Creating table: {$tablePrefix}tags");
        Schema::connection($connection)->create($tablePrefix . 'tags', function ($table) {
            $table->id();
            $table->string('name')->unique();
        });
        
        // Create headings table
        $this->info("Creating table: {$tablePrefix}headings");
        Schema::connection($connection)->create($tablePrefix . 'headings', function ($table) use ($tablePrefix) {
            $table->id();
            $table->foreignId('document_id')->constrained($tablePrefix . 'documents')->onDelete('cascade');
            $table->string('text');
            $table->unsignedTinyInteger('level');
            $table->string('section');
        });
        
        // Create document_tags pivot table
        $this->info("Creating table: {$tablePrefix}document_tags");
        Schema::connection($connection)->create($tablePrefix . 'document_tags', function ($table) use ($tablePrefix) {
            $table->id();
            $table->foreignId('document_id')->index()->constrained($tablePrefix . 'documents');
            $table->foreignId('tag_id')->index()->constrained($tablePrefix . 'tags');
        });
    }

    protected function addStorageDisk(): void
    {
        if (config('filesystems.disks.prezet')) {
            $this->warn('Skipping storage disk setup: the prezet storage disk already exists.');

            return;
        }
        $this->info('Adding prezet storage disk');

        $configFile = config_path('filesystems.php');
        $config = file_get_contents($configFile);
        if (! $config) {
            $this->error('Failed to read filesystem config file: '.$configFile);

            return;
        }

        $diskConfig = "\n        'prezet' => [\n            'driver' => 'local',\n            'root' => base_path('prezet'),\n            'throw' => false,\n        ],";

        $disksPosition = strpos($config, "'disks' => [");
        if ($disksPosition !== false) {
            $disksPosition += strlen("'disks' => [");
            $newConfig = substr_replace($config, $diskConfig, $disksPosition, 0);
            file_put_contents($configFile, $newConfig);
        }
    }
}
