<?php

namespace App\Services\UserRoles;

use App\Models\PartnerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

/**
 * Factory Method creator and shared role behaviour for User & Partner Management.
 */
abstract class UserRoleHandler
{
    final public static function for(string $role): self
    {
        return match ($role) {
            User::ROLE_FOOD_DONOR => new FoodDonorRoleHandler(),
            User::ROLE_CHARITY => new CharityRoleHandler(),
            User::ROLE_VOLUNTEER => new VolunteerRoleHandler(),
            User::ROLE_ADMIN => new AdminRoleHandler(),
            default => throw new InvalidArgumentException("Unsupported user role [$role]."),
        };
    }

    public function register(array $data, bool $createdByAdmin = false): User
    {
        $user = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'phone_no' => $data['phone_no'] ?? null,
            'role' => $this->role(),
            'account_status' => $this->initialAccountStatus($createdByAdmin),
        ]);

        $this->createPartnerProfile($user, $data);

        return $user;
    }

    abstract public function role(): string;

    public function mayLogin(User $user): bool
    {
        return in_array($user->account_status, [User::STATUS_PENDING, User::STATUS_ACTIVE], true);
    }

    public function mayAccessRoleFeatures(User $user): bool
    {
        return $user->account_status === User::STATUS_ACTIVE
            && ($this->role() === User::ROLE_ADMIN
                || $user->partnerProfile?->verification_status === 'APPROVED');
    }

    public function deletionBlocker(User $user): ?string
    {
        return null;
    }

    /** @return array<int, string> */
    abstract public function allowedDocumentTypes(): array;

    protected function initialAccountStatus(bool $createdByAdmin): string
    {
        return $this->role() === User::ROLE_ADMIN && $createdByAdmin
            ? User::STATUS_ACTIVE
            : User::STATUS_PENDING;
    }

    protected function createPartnerProfile(User $user, array $data): void
    {
        PartnerProfile::create([
            'user_id' => $user->user_id,
            'address' => $data['address'] ?? null,
            'verification_status' => 'PENDING',
        ]);
    }
}