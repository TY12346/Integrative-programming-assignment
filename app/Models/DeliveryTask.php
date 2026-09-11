<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : app/Models/DeliveryTask.php
 * Purpose: Eloquent ORM model for a delivery task.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryTask extends Model
{
    public const ASSIGNED = 'ASSIGNED';
    public const PICKED_UP = 'PICKED_UP';
    public const DELIVERED = 'DELIVERED';
    public const CANCELLED = 'CANCELLED';

    public const STATUSES = [self::ASSIGNED, self::PICKED_UP, self::DELIVERED, self::CANCELLED];

    public $timestamps = false;

    protected $primaryKey = 'delivery_id';

    protected $fillable = [
        'reservation_id',
        'volunteer_id',
        'pickup_address',
        'delivery_address',
        'delivery_status',
        'delivery_notes',
        'picked_up_at',
        'delivered_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function volunteer()
    {
        return $this->belongsTo(PartnerProfile::class, 'volunteer_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(DeliveryStatusHistory::class, 'delivery_id');
    }

    public function impacts()
    {
        return $this->hasMany(DeliveryImpact::class, 'delivery_id');
    }

    public function isFinal(): bool
    {
        return in_array($this->delivery_status, [self::DELIVERED, self::CANCELLED], true);
    }

    public function allowedNextStatuses(): array
    {
        return match ($this->delivery_status) {
            self::ASSIGNED => [self::PICKED_UP, self::CANCELLED],
            self::PICKED_UP => [self::DELIVERED, self::CANCELLED],
            default => [],
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->delivery_status) {
            self::ASSIGNED => 'bg-secondary',
            self::PICKED_UP => 'bg-primary',
            self::DELIVERED => 'bg-success',
            self::CANCELLED => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}
