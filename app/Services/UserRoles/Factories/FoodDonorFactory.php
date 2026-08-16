<?php

namespace App\Services\UserRoles\Factories;

use App\Services\UserRoles\FoodDonorRoleHandler;
use App\Services\UserRoles\UserRoleHandler;

final class FoodDonorFactory extends UserRoleFactory
{
    protected function createHandler(): UserRoleHandler
    {
        return new FoodDonorRoleHandler();
    }
}
