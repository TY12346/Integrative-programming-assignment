<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const ROLE_FOOD_DONOR = 'FOOD_DONOR';
    public const ROLE_CHARITY = 'CHARITY';
    public const ROLE_VOLUNTEER = 'VOLUNTEER';
    public const ROLE_ADMIN = 'ADMIN';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_DELETED = 'DELETED';
    
    
    public $timestamps = false;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'full_name',
        'email',
        'password_hash',
        'phone_no',
        'role',
        'account_status',
    ];

    protected $hidden = ['password_hash'];

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function partnerProfile()
    {
        return $this->hasOne(PartnerProfile::class, 'user_id');
    }

    public function sessions()
    {
        return $this->hasMany(UserSession::class, 'user_id');
    }

    public function verificationReviews()
    {
        return $this->hasMany(VerificationReview::class, 'reviewed_by');
    }

    public function donationStatusHistories()
    {
        return $this->hasMany(DonationStatusHistory::class, 'changed_by');
    }

    public function deliveryStatusHistories()
    {
        return $this->hasMany(DeliveryStatusHistory::class, 'changed_by');
    }

    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }
}
