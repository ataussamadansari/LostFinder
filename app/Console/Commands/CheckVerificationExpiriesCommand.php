<?php

namespace App\Console\Commands;

use App\Services\VerificationService;
use Illuminate\Console\Command;

class CheckVerificationExpiriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verification:check-expiries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan driver and vehicle documents for expiration and update review statuses';

    /**
     * Execute the console command.
     */
    public function handle(VerificationService $service): int
    {
        $this->info('Scanning driver and vehicle documents for expiration...');

        $results = $service->checkExpiries();

        $this->line("Driver documents expired: {$results['driver_docs_expired']}");
        $this->line("Vehicle documents expired: {$results['vehicle_docs_expired']}");
        $this->info('Verification expiry scan completed successfully.');

        return Command::SUCCESS;
    }
}
