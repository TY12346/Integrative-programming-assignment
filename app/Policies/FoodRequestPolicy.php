<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Purpose: Central authorisation rules for food requests, applied by both the
 *          web controller and the REST web service.
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
