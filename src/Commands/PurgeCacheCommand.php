<?php

namespace Prezet\Prezet\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PurgeCacheCommand extends Command
{
    public $signature = 'prezet:purge';

    public $description = 'Purges the Cloudflare cache.';

    public function handle(): int
    {
        $token = config('services.cloudflare.token');
        if (! is_string($token) || $token === '') {
            $this->error('Cloudflare API Token not found in services.cloudflare.token.');

            return self::FAILURE;
        }

        $zoneId = config('services.cloudflare.zone_id');
        if (! is_string($zoneId) || $zoneId === '') {
            $this->error('Cloudflare Zone ID not found in services.cloudflare.zone_id.');

            return self::FAILURE;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
        ])->post('https://api.cloudflare.com/client/v4/zones/'.$zoneId.'/purge_cache', [
            'purge_everything' => true,
        ]);

        if ($response->failed()) {
            $this->error($response->body());

            return self::FAILURE;
        }

        $this->info('Cache purged.');

        return self::SUCCESS;
    }
}
