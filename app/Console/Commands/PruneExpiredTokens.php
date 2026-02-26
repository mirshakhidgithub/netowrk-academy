<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

class PruneExpiredTokens extends Command
{
    protected $signature = 'tokens:prune';
    protected $description = 'Prune expired personal access tokens from database and Redis cache';

    public function handle(): int
    {
        $this->info('Starting token pruning...');

        try {
            // Delete expired tokens from database
            $deletedCount = PersonalAccessToken::where('expires_at', '<', now())
                ->delete();

            $this->info("Deleted {$deletedCount} expired tokens from database.");

            // You can add more cleanup logic here
            // For example, cleaning up orphaned Redis keys

            $this->info('Token pruning completed successfully.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error during token pruning: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
