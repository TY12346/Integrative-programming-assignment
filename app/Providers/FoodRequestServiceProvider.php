<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Providers/FoodRequestServiceProvider.php
 * Purpose: Wires the Food Request Management module into the framework.
 *
 *          This is where three of the module's design patterns are assembled:
 *            - SINGLETON  : the repository, service and filter pipeline are
 *                           registered as singletons in Laravel's service
 *                           container, so one shared instance is injected
 *                           everywhere instead of being created per class.
 *            - STRATEGY   : the concrete donation filters are listed here, so a
 *                           new filter is a one line change.
 *            - OBSERVER   : the Reservation model observer is attached here.
 *          It also binds the DonationGateway abstraction to the local or the
 *          HTTP implementation according to configuration, and registers the
 *          authorisation policy.
 */

namespace App\Providers;

use App\Filters\Donation\CategoryFilter;
use App\Filters\Donation\DonationFilterPipeline;
use App\Filters\Donation\ExpiryWindowFilter;
use App\Filters\Donation\KeywordFilter;
use App\Filters\Donation\MinQuantityFilter;
use App\Filters\Donation\StorageTypeFilter;
use App\Models\DeliveryTask;
use App\Models\FoodRequest;
use App\Models\Reservation;
use App\Observers\DeliveryTaskObserver;
use App\Observers\ReservationObserver;
use App\Policies\FoodRequestPolicy;
use App\Repositories\FoodRequestRepository;
use App\Services\FoodRequestService;
use App\Services\Gateways\DonationGateway;
use App\Services\Gateways\HttpDonationGateway;
use App\Services\Gateways\LocalDonationGateway;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class FoodRequestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // STRATEGY: the ordered set of filters used to narrow active donations.
        $this->app->singleton(DonationFilterPipeline::class, fn () => new DonationFilterPipeline(
            new KeywordFilter(),
            new CategoryFilter(),
            new StorageTypeFilter(),
            new MinQuantityFilter(),
            new ExpiryWindowFilter(),
        ));

        // SINGLETON: shared repository and service instances.
        $this->app->singleton(FoodRequestRepository::class);
        $this->app->singleton(FoodRequestService::class);

        // Integration boundary with the Food Donation Management module (3.2).
        $this->app->singleton(LocalDonationGateway::class, fn ($app) => new LocalDonationGateway(
            $app->make(DonationFilterPipeline::class)
        ));

        $this->app->singleton(DonationGateway::class, function ($app) {
            $local = $app->make(LocalDonationGateway::class);

            if (config('foodlink.donation_gateway') !== 'http') {
                return $local;
            }

            return new HttpDonationGateway(
                $local,
                (string) config('foodlink.api_base_url'),
                (int) config('foodlink.api_timeout', 5),
            );
        });
    }

    public function boot(): void
    {
        // OBSERVER: keeps request quantities and status in step with any change
        // to a reservation, including changes made by module 3.4.
        Reservation::observe(ReservationObserver::class);

        // Integration draft with the Delivery and Impact Tracking module (3.4):
        // a completed or cancelled delivery is translated into the reservation
        // outcome my module tracks.
        DeliveryTask::observe(DeliveryTaskObserver::class);

        Gate::policy(FoodRequest::class, FoodRequestPolicy::class);

        // The interface is styled with Bootstrap 5, so paginate() must render
        // Bootstrap markup instead of the framework default.
        Paginator::useBootstrapFive();
    }
}
