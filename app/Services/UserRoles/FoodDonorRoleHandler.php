<?php

namespace App\Services\UserRoles;

use App\Models\User;

final class FoodDonorRoleHandler extends UserRoleHandler
{
    public function role(): string { return User::ROLE_FOOD_DONOR; }

    public function allowedDocumentTypes(): array { return ['BUSINESS_REGISTRATION', 'FOOD_SAFETY_CERTIFICATE', 'IDENTITY_DOCUMENT']; }

    public function deletionBlocker(User $user): ?string
    {
        $hasActiveDonations = $user->partnerProfile?->donations()
            ->whereIn('donation_status', ['AVAILABLE', 'RESERVED'])->exists() ?? false;

        return $hasActiveDonations ? 'Cancel or complete all active donations before deleting your account.' : null;
    }
}