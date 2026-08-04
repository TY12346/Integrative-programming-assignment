<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Models/Reservation.php
 * Purpose: Eloquent model for the link between a food request (module 3.3) and
 *          a food donation (module 3.2). A reservation is the record of the
 *          quantity a donor has committed, and it is what module 3.4 turns into
 *          a delivery task. Status constants live here so that every module
 *          agrees on the same vocabulary.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    public const PENDING = 'PENDING';
    public const CONFIRMED = 'CONFIRMED';
    public const CANCELLED = 'CANCELLED';
    public const COMPLETED = 'COMPLETED';

    /** Committed but not yet delivered - counted as "reserved quantity". */
    public const ACTIVE_STATUSES = [self::PENDING, self::CONFIRMED];

    public $timestamps = false;

    protected $primaryKey = 'reservation_id';

    protected $fillable = [
        'request_id',
        'donation_id',
        'reserved_quantity',
        'reservation_status',
        'pickup_deadline',
    ];

    protected $casts = [
        'reserved_quantity' => 'decimal:2',
        'pickup_deadline' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(FoodRequest::class, 'request_id');
    }

    public function donation()
    {
        return $this->belongsTo(FoodDonation::class, 'donation_id');
    }

    public function deliveryTask()
    {
        return $this->hasOne(DeliveryTask::class, 'reservation_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('reservation_status', self::ACTIVE_STATUSES);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('reservation_status', self::COMPLETED);
    }

    public function isActive(): bool
    {
        return in_array($this->reservation_status, self::ACTIVE_STATUSES, true);
    }

    /** A reservation can only be withdrawn before a delivery has completed it. */
    public function isCancellable(): bool
    {
        return $this->isActive();
    }

    public function statusBadgeClass(): string
    {
        return match ($this->reservation_status) {
            self::COMPLETED => 'bg-success',
            self::CONFIRMED => 'bg-primary',
            self::CANCELLED => 'bg-dark',
            default => 'bg-secondary',
        };
    }
}
