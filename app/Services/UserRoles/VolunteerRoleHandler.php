<?php

/**
 Author: Ong Tin Yin
 */

namespace App\Services\UserRoles;

use App\Models\User;
use App\Services\ModuleIntegration\DeliveryObligationApiClient;
use Throwable;

final class VolunteerRoleHandler extends UserRoleHandler
{
    public function __construct(
        private readonly DeliveryObligationApiClient $deliveryObligations
    ) {
    }

    public function role(): string
    {
        return User::ROLE_VOLUNTEER;
    }

    public function allowedDocumentTypes(): array
    {
        return [
            'IDENTITY_DOCUMENT',
            'DRIVING_LICENSE',
            'VEHICLE_INSURANCE',
        ];
    }

    public function deletionBlocker(User $user): ?string
    {
        $volunteerID = $user->partnerProfile?->profile_id;

        if ($volunteerID === null) {
            return 'Your volunteer profile could not be found.';
        }

        try {
            $hasActiveDeliveries =
                $this->deliveryObligations
                    ->hasActiveDeliveries((int) $volunteerID);
        } catch (Throwable $exception) {
            report($exception);

            return 'We could not verify your delivery obligations. '
                .'Your account was not deleted. Please try again later.';
        }

        return $hasActiveDeliveries
            ? 'Complete or cancel all assigned delivery tasks '
                .'before deleting your account.'
            : null;
    }
}