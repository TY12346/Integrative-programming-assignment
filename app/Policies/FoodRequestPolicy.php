<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Policies/FoodRequestPolicy.php
 * Purpose: Central authorisation rules for food requests, applied by both the
 *          web controller and the REST web service.
 *
 * Secure coding: this is the defence against broken access control (insecure
 * direct object references). Ownership is compared against the partner profile
 * of the logged in user, so changing the id in the URL or in an API call
 * returns 403 instead of another charity's data. The verification status issued
 * by the User and Partner Management module (3.1) is also enforced here.
 */

namespace App\Policies;

use App\Models\FoodRequest;
use App\Models\User;

class FoodRequestPolicy
{
    /** Only a verified charity may take part in the request workflow. */
    public function create(User $user): bool
    {
        return $this->isVerifiedCharity($user);
    }

    public function viewAny(User $user): bool
    {
        return $user->role === 'CHARITY' || $user->role === 'ADMIN';
    }

    public function view(User $user, FoodRequest $request): bool
    {
        return $user->role === 'ADMIN' || $this->owns($user, $request);
    }

    public function update(User $user, FoodRequest $request): bool
    {
        return $this->isVerifiedCharity($user)
            && $this->owns($user, $request)
            && $request->state()->canEdit();
    }

    public function cancel(User $user, FoodRequest $request): bool
    {
        return $this->isVerifiedCharity($user)
            && $this->owns($user, $request)
            && $request->state()->canCancel();
    }

    public function reserve(User $user, FoodRequest $request): bool
    {
        return $this->isVerifiedCharity($user)
            && $this->owns($user, $request)
            && $request->state()->canReserve();
    }

    private function owns(User $user, FoodRequest $request): bool
    {
        $profileId = $user->partnerProfile?->profile_id;

        return $profileId !== null && (int) $request->charity_id === (int) $profileId;
    }

    private function isVerifiedCharity(User $user): bool
    {
        return $user->role === 'CHARITY'
            && $user->account_status === 'ACTIVE'
            && $user->partnerProfile?->verification_status === 'APPROVED';
    }
}
