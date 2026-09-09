<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodDonation extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'donation_id';

    protected $fillable = [
        'donor_id',
        'category_id',
        'food_name',
        'description',
        'donation_quantity',
        'current_quantity',
        'measurement_unit',
        'expiry_datetime',
        'pickup_address',
        'storage_type',
        'halal_status',
        'donation_status',
    ];

    protected $casts = ['expiry_datetime' => 'datetime'];

    public function donor()
    {
        return $this->belongsTo(PartnerProfile::class, 'donor_id');
    }

    public function category()
    {
        return $this->belongsTo(FoodCategory::class, 'category_id');
    }

    public function photos()
    {
        return $this->hasMany(DonationPhoto::class, 'donation_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(DonationStatusHistory::class, 'donation_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'donation_id');
    }

    /** User-facing label; COMPLETED is shown as Collected. DB value is unchanged. */
    public function statusLabel(): string
    {
        return match ($this->donation_status) {
            'AVAILABLE' => 'Available',
            'RESERVED' => 'Reserved',
            'COMPLETED' => 'Collected',
            'CANCELLED' => 'Cancelled',
            'EXPIRED' => 'Expired',
            default => (string) $this->donation_status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->donation_status) {
            'AVAILABLE' => 'bg-success',
            'RESERVED' => 'bg-primary',
            'COMPLETED' => 'bg-secondary',
            'CANCELLED' => 'bg-dark',
            'EXPIRED' => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}
