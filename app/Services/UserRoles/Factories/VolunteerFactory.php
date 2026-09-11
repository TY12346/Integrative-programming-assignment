<?php

/**
 * Author: Ong Tin Yin
 */

namespace App\Services\UserRoles\Factories;

use App\Services\ModuleIntegration\DeliveryObligationApiClient;
use App\Services\UserRoles\UserRoleHandler;
use App\Services\UserRoles\VolunteerRoleHandler;

final class VolunteerFactory extends UserRoleFactory
{
    public function __construct(
        private readonly DeliveryObligationApiClient $deliveryObligations
    ) {
    }

    protected function createHandler(): UserRoleHandler
    {
        return new VolunteerRoleHandler(
            $this->deliveryObligations
        );
    }
}