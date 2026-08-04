<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Console/Commands/RefreshRequestStatuses.php
 * Purpose: Supports "Monitor Fulfillment Deadline". A food request whose
 *          deadline passes has to move to EXPIRED even when nobody opens the
 *          page, so this command sweeps the active requests. It is scheduled
 *          hourly in routes/console.php and can also be run by hand during the
 *          demo:  php artisan foodlink:refresh-requests
 */

namespace App\Console\Commands;

use App\Services\FoodRequestService;
use Illuminate\Console\Command;

class RefreshRequestStatuses extends Command
{
    protected $signature = 'foodlink:refresh-requests';

    protected $description = 'Flag food requests whose fulfilment deadline has passed (module 3.3).';

    public function handle(FoodRequestService $service): int
    {
        $changed = $service->expireOverdueRequests();

        $this->info($changed.' food request(s) updated after their fulfilment deadline passed.');

        return self::SUCCESS;
    }
}
