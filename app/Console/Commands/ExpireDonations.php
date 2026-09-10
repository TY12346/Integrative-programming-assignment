<?php
/* Lau Ke Xin */

namespace App\Console\Commands;

use App\Services\DonationExpiryService;
use Illuminate\Console\Command;

class ExpireDonations extends Command
{
    protected $signature = 'foodlink:expire-donations';

    protected $description = 'Mark AVAILABLE food donations as EXPIRED when their expiry datetime has passed (module 3.2).';

    public function handle(DonationExpiryService $service): int
    {
        $changed = $service->expireOverdueDonations();

        $this->info($changed.' donation(s) marked as EXPIRED after their expiry datetime passed.');

        return self::SUCCESS;
    }
}
