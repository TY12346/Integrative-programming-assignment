<?php

namespace App\Services\UserRoles\Factories;

use App\Services\UserRoles\UserRoleHandler;
use App\Services\UserRoles\VolunteerRoleHandler;

final class VolunteerFactory extends UserRoleFactory
{
    protected function createHandler(): UserRoleHandler
    {
        return new VolunteerRoleHandler();
    }
}