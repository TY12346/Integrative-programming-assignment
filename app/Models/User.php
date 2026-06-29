<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
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
