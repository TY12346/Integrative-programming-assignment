<?php

namespace App\Services\UserRoles;

use App\Models\User;

final class CharityRoleHandler extends UserRoleHandler
{
    public function role(): string { return User::ROLE_CHARITY; }

    public function allowedDocumentTypes(): array { return ['CHARITY_REGISTRATION', 'ORGANISATION_LICENSE', 'IDENTITY_DOCUMENT']; }

    public function deletionBlocker(User $user): ?string
    {
        $hasActiveRequests = $user->partnerProfile?->requests()
            ->whereIn('request_status', ['PENDING', 'PARTIALLY_FULFILLED'])->exists() ?? false;

        return $hasActiveRequests ? 'Cancel or complete all active food requests before deleting your account.' : null;
    }
}