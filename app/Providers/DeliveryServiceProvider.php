<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : app/Providers/DeliveryServiceProvider.php
 * Purpose: Registers the module's Observer Pattern implementation.
 */

namespace App\Providers;

use App\Models\DeliveryTask;
use App\Observers\DeliveryImpactObserver;
use Illuminate\Support\ServiceProvider;

class DeliveryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        DeliveryTask::observe(DeliveryImpactObserver::class);
    }
}
