<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerProfile extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'profile_id';

    protected $fillable = ['user_id', 'address', 'verification_status'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documents()
    {
        return $this->hasMany(VerificationDocument::class, 'partner_id');
    }

    public function reviews()
    {
        return $this->hasMany(VerificationReview::class, 'partner_id');
    }

    public function donations()
    {
        return $this->hasMany(FoodDonation::class, 'donor_id');
    }

    public function requests()
    {
        return $this->hasMany(FoodRequest::class, 'charity_id');
    }

    public function deliveryTasks()
    {
        return $this->hasMany(DeliveryTask::class, 'volunteer_id');
    }
}
