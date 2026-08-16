<?php

namespace App\Services\UserRoles\Factories;

use App\Services\UserRoles\AdminRoleHandler;
use App\Services\UserRoles\UserRoleHandler;

final class AdminFactory extends UserRoleFactory
{
    protected function createHandler(): UserRoleHandler
    {
        return new AdminRoleHandler();
    }
}