<?php

namespace Prezet\Prezet\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;

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
            $this->addDatabase();
            $this->publishConfig();

            // run in separate process so config changes above are applied
            Process::run('php artisan prezet:index --fresh');
            $this->info('Prezet has been successfully installed!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('An error occurred during installation: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function addDatabase(): void
    {
        if (config('database.connections.prezet')) {
            $this->warn('Skipping database setup: the prezet database connection already exists.');

            return;
        }
        $this->info('Adding prezet database');
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
