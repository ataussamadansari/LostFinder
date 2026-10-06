<?php

namespace App\Console\Commands;

use App\Services\JourneyService;
use Illuminate\Console\Command;

class ExpireJourneysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'journeys:expire-inactive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically expire active journeys that have surpassed their 72-hour TTL';

    /**
     * Execute the console command.
     */
    public function handle(JourneyService $service): int
    {
        $this->info('Scanning active journeys for expiration...');

        $expiredCount = $service->expireInactiveJourneys();

        $this->line("Expired journeys processed: {$expiredCount}");
        $this->info('Journey expiration check completed successfully.');

        return Command::SUCCESS;
    }
}
