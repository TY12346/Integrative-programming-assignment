<?php

namespace App\Services\UserRoles;

use App\Models\User;

final class AdminRoleHandler extends UserRoleHandler
{
    public function role(): string { return User::ROLE_ADMIN; }

    public function allowedDocumentTypes(): array { return []; }

    protected function createPartnerProfile(User $user, array $data): void {}

    public function deletionBlocker(User $user): ?string
    {
        return 'Administrator accounts cannot be self-deleted.';
    }
}