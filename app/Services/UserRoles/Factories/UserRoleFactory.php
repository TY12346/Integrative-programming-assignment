<?php

namespace App\Services\UserRoles\Factories;

use App\Models\User;
use App\Services\UserRoles\UserRoleHandler;

/**
 * Factory Method creator for role-specific user management.
 *
 * The shared operations depend only on the UserRoleHandler product. Concrete
 * creators decide which product to instantiate by overriding createHandler().
 */
abstract class UserRoleFactory
{
    /** The Factory Method implemented by every concrete creator. */
    abstract protected function createHandler(): UserRoleHandler;

    /** Shared registration operation built around the factory method. */
    final public function register(array $data, bool $createdByAdmin = false): User
    {
        return $this->createHandler()->register($data, $createdByAdmin);
    }

    /** Exposes a freshly created product for other role-dependent features. */
    final public function handler(): UserRoleHandler
    {
        return $this->createHandler();
    }
}
