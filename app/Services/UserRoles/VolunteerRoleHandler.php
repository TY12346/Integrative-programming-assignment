<?php

namespace App\Services\UserRoles;

use App\Models\User;

final class VolunteerRoleHandler extends UserRoleHandler
{
    public function role(): string { return User::ROLE_VOLUNTEER; }

    public function allowedDocumentTypes(): array { return ['IDENTITY_DOCUMENT', 'DRIVING_LICENSE', 'VEHICLE_INSURANCE']; }

    public function deletionBlocker(User $user): ?string
    {
        $hasAssignedTasks = $user->partnerProfile?->deliveryTasks()
            ->whereIn('delivery_status', ['ASSIGNED', 'PICKED_UP'])->exists() ?? false;

        return $hasAssignedTasks ? 'Complete or cancel all assigned delivery tasks before deleting your account.' : null;
    }
}