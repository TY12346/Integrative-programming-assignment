<?php

namespace App\Models {
    class User
    {
        public const ROLE_FOOD_DONOR = 'FOOD_DONOR';
        public const ROLE_CHARITY = 'CHARITY';
        public const ROLE_VOLUNTEER = 'VOLUNTEER';
        public const ROLE_ADMIN = 'ADMIN';
        public const STATUS_PENDING = 'PENDING';
        public const STATUS_ACTIVE = 'ACTIVE';

        public mixed $partnerProfile = null;
        public string $account_status = self::STATUS_PENDING;
    }

    class PartnerProfile {}
}

namespace {
    require_once __DIR__.'/../app/Services/UserRoles/UserRoleHandler.php';
    require_once __DIR__.'/../app/Services/UserRoles/FoodDonorRoleHandler.php';
    require_once __DIR__.'/../app/Services/UserRoles/CharityRoleHandler.php';
    require_once __DIR__.'/../app/Services/UserRoles/VolunteerRoleHandler.php';
    require_once __DIR__.'/../app/Services/UserRoles/AdminRoleHandler.php';

    use App\Services\UserRoles\AdminRoleHandler;
    use App\Services\UserRoles\CharityRoleHandler;
    use App\Services\UserRoles\FoodDonorRoleHandler;
    use App\Services\UserRoles\UserRoleHandler;
    use App\Services\UserRoles\VolunteerRoleHandler;

    $cases = [
        'FOOD_DONOR' => FoodDonorRoleHandler::class,
        'CHARITY' => CharityRoleHandler::class,
        'VOLUNTEER' => VolunteerRoleHandler::class,
        'ADMIN' => AdminRoleHandler::class,
    ];

    foreach ($cases as $role => $expected) {
        $actual = UserRoleHandler::for($role);
        assert($actual instanceof $expected);
        assert($actual->role() === $role);
    }

    assert(UserRoleHandler::for('VOLUNTEER')->allowedDocumentTypes() !== []);
    assert(UserRoleHandler::for('ADMIN')->allowedDocumentTypes() === []);

    try {
        UserRoleHandler::for('UNSUPPORTED');
        throw new RuntimeException('Unsupported role did not throw.');
    } catch (InvalidArgumentException) {
        // Expected.
    }

    echo "User role Factory Method self-check passed.\n";
}