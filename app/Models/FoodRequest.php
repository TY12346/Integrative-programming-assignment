<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Models/FoodRequest.php
 * Purpose: Eloquent ORM model (the "M" of MVC) for a charity food request.
 *          It maps the food_requests table, declares its relationships, and
 *          exposes the derived values used by the module: reserved quantity,
 *          outstanding quantity, deadline urgency and the current state object.
 */

namespace App\Models;

use App\Domain\RequestStatus\RequestProgress;
use App\Domain\RequestStatus\RequestState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FoodRequest extends Model
{
    /** The table uses a MySQL default for created_at, so Eloquent timestamps are off. */
    public $timestamps = false;

    protected $primaryKey = 'request_id';

    /**
     * Secure coding: mass-assignment whitelist. charity_id, fulfilled_quantity
     * and request_status are deliberately NOT fillable, so a crafted form field
     * can never reassign a request to another charity or fake its progress;
     * those columns are only written by the service layer.
     */
    protected $fillable = [
        'category_id',
        'requested_quantity',
        'unit',
        'notes',
        'request_deadline',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:2',
        'fulfilled_quantity' => 'decimal:2',
        'request_deadline' => 'datetime',
        'created_at' => 'datetime',
    ];

    /* ------------------------------------------------------------------ */
    /* Relationships                                                       */
    /* ------------------------------------------------------------------ */

    public function charity()
    {
        return $this->belongsTo(PartnerProfile::class, 'charity_id');
    }

    public function category()
    {
        return $this->belongsTo(FoodCategory::class, 'category_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'request_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(RequestStatusHistory::class, 'request_id');
    }

    /* ------------------------------------------------------------------ */
    /* Query scopes                                                        */
    /* ------------------------------------------------------------------ */

    /** Restrict a query to one charity - the guard behind every dashboard list. */
    public function scopeOwnedBy(Builder $query, int $charityId): Builder
    {
        return $query->where('charity_id', $charityId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('request_status', [
            RequestState::PENDING,
            RequestState::PARTIALLY_FULFILLED,
            RequestState::EXPIRED,
        ]);
    }

    public function scopeHistorical(Builder $query): Builder
    {
        return $query->whereIn('request_status', [RequestState::COMPLETED, RequestState::CANCELLED]);
    }

    /**
     * Adds a reserved_quantity aggregate to the result set so the dashboard can
     * show committed quantities without an N+1 query per row.
     */
    public function scopeWithReservedQuantity(Builder $query): Builder
    {
        return $query->withSum([
            'reservations as reserved_quantity' => fn ($q) => $q->whereIn('reservation_status', Reservation::ACTIVE_STATUSES),
        ], 'reserved_quantity');
    }

    /* ------------------------------------------------------------------ */
    /* Derived attributes                                                  */
    /* ------------------------------------------------------------------ */

    /** State pattern entry point - never compare request_status directly. */
    public function state(): RequestState
    {
        return RequestState::for($this->request_status);
    }

    /**
     * "Track Reserved Quantity": quantity donors have committed but not yet
     * delivered. Uses the pre-loaded aggregate when the caller used
     * withReservedQuantity(), otherwise falls back to a single SUM query.
     */
    public function getReservedQuantityAttribute($value): float
    {
        if ($value !== null) {
            return (float) $value;
        }

        return (float) $this->reservations()
            ->whereIn('reservation_status', Reservation::ACTIVE_STATUSES)
            ->sum('reserved_quantity');
    }

    public function progress(): RequestProgress
    {
        return new RequestProgress(
            requested: (float) $this->requested_quantity,
            fulfilled: (float) $this->fulfilled_quantity,
            reserved: $this->reserved_quantity,
            deadlinePassed: $this->isPastDeadline(),
        );
    }

    public function isPastDeadline(): bool
    {
        return $this->request_deadline !== null && $this->request_deadline->isPast();
    }

    /**
     * "Monitor Fulfillment Deadline": urgency is derived from the deadline
     * rather than stored, so it can never drift out of date.
     */
    public function getUrgencyAttribute(): string
    {
        if ($this->state()->isFinal() || $this->request_deadline === null) {
            return 'NONE';
        }

        if ($this->isPastDeadline()) {
            return 'OVERDUE';
        }

        $hoursLeft = (int) now()->diffInHours($this->request_deadline, false);

        return $hoursLeft <= (int) config('foodlink.request.urgent_within_hours', 24) ? 'URGENT' : 'NORMAL';
    }

    public function getHoursToDeadlineAttribute(): ?int
    {
        return $this->request_deadline === null
            ? null
            : (int) now()->diffInHours($this->request_deadline, false);
    }
}
