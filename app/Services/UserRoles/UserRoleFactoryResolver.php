<?php

namespace App\Services\UserRoles;

use App\Models\User;
use App\Services\UserRoles\Factories\AdminFactory;
use App\Services\UserRoles\Factories\CharityFactory;
use App\Services\UserRoles\Factories\FoodDonorFactory;
use App\Services\UserRoles\Factories\UserRoleFactory;
use App\Services\UserRoles\Factories\VolunteerFactory;
use InvalidArgumentException;

/** Resolves the concrete creator at the application's role-string boundary. */
final class UserRoleFactoryResolver
{
    public function resolve(string $role): UserRoleFactory
    {
        return match ($role) {
            User::ROLE_FOOD_DONOR => new FoodDonorFactory(),
            User::ROLE_CHARITY => new CharityFactory(),
            User::ROLE_VOLUNTEER => new VolunteerFactory(),
            User::ROLE_ADMIN => new AdminFactory(),
            default => throw new InvalidArgumentException("Unsupported user role [$role]."),
        };
    }
}