<?php

namespace Prezet\Prezet\Commands;

use Illuminate\Console\Command;
use Prezet\Prezet\Exceptions\FrontmatterException;
use Prezet\Prezet\Exceptions\FrontmatterMissingException;
use Prezet\Prezet\Prezet;

class UpdateIndexCommand extends Command
{
    public $signature = 'prezet:index {--fresh : Fresh recreates the database}';

    public $description = 'Updates the prezet index. Use --fresh to recreate the database/tables.';

    public function handle(): int
    {
        try {
            if ($this->option('fresh')) {
                $this->info('Recreating database...');
                Prezet::createIndex();
            }

            $this->info('Updating index...');
            Prezet::updateIndex();

            $this->info('Index updated successfully.');
        } catch (FrontmatterMissingException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (FrontmatterException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error('An error occurred: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
