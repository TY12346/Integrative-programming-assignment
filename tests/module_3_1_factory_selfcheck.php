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
    require_once __DIR__.'/../app/Services/UserRoles/Factories/UserRoleFactory.php';
    require_once __DIR__.'/../app/Services/UserRoles/Factories/FoodDonorFactory.php';
    require_once __DIR__.'/../app/Services/UserRoles/Factories/CharityFactory.php';
    require_once __DIR__.'/../app/Services/UserRoles/Factories/VolunteerFactory.php';
    require_once __DIR__.'/../app/Services/UserRoles/Factories/AdminFactory.php';
    require_once __DIR__.'/../app/Services/UserRoles/UserRoleFactoryResolver.php';

    use App\Services\UserRoles\AdminRoleHandler;
    use App\Services\UserRoles\CharityRoleHandler;
    use App\Services\UserRoles\FoodDonorRoleHandler;
    use App\Services\UserRoles\Factories\AdminFactory;
    use App\Services\UserRoles\Factories\CharityFactory;
    use App\Services\UserRoles\Factories\FoodDonorFactory;
    use App\Services\UserRoles\Factories\VolunteerFactory;
    use App\Services\UserRoles\UserRoleFactoryResolver;
    use App\Services\UserRoles\VolunteerRoleHandler;

    $cases = [
        'FOOD_DONOR' => [FoodDonorFactory::class, FoodDonorRoleHandler::class],
        'CHARITY' => [CharityFactory::class, CharityRoleHandler::class],
        'VOLUNTEER' => [VolunteerFactory::class, VolunteerRoleHandler::class],
        'ADMIN' => [AdminFactory::class, AdminRoleHandler::class],
    ];

    $resolver = new UserRoleFactoryResolver();
    foreach ($cases as $role => [$expectedCreator, $expectedProduct]) {
        $creator = $resolver->resolve($role);
        $product = $creator->handler();
        assert($creator instanceof $expectedCreator);
        assert($product instanceof $expectedProduct);
        assert($product->role() === $role);
    }

    assert($resolver->resolve('VOLUNTEER')->handler()->allowedDocumentTypes() !== []);
    assert($resolver->resolve('ADMIN')->handler()->allowedDocumentTypes() === []);

    try {
        $resolver->resolve('UNSUPPORTED');
        throw new RuntimeException('Unsupported role did not throw.');
    } catch (InvalidArgumentException) {
        // Expected.
    }

    echo "Genuine user role Factory Method self-check passed.\n";
}