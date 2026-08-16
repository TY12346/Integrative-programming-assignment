<?php

namespace App\Services\UserRoles\Factories;

use App\Services\UserRoles\CharityRoleHandler;
use App\Services\UserRoles\UserRoleHandler;

final class CharityFactory extends UserRoleFactory
{
    protected function createHandler(): UserRoleHandler
    {
        return new CharityRoleHandler();
    }
}
