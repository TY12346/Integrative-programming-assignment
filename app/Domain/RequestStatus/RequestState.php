<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Domain/RequestStatus/RequestState.php
 * Purpose: Abstract participant of the STATE design pattern. Every food request
 *          status is represented by its own class that knows which operations
 *          it permits and which state it moves to next. The controllers, views
 *          and services therefore never contain a chain of status if-statements.
 */

namespace App\Domain\RequestStatus;

use InvalidArgumentException;

abstract class RequestState
{
    public const PENDING = 'PENDING';
    public const PARTIALLY_FULFILLED = 'PARTIALLY_FULFILLED';
    public const COMPLETED = 'COMPLETED';
    public const CANCELLED = 'CANCELLED';
    public const EXPIRED = 'EXPIRED';

    /** Value persisted in food_requests.request_status. */
    abstract public function code(): string;

    /** Human readable label shown in the views. */
    abstract public function label(): string;

    /** Bootstrap badge class used by the dashboard. */
    abstract public function badgeClass(): string;

    /**
     * Core of the state pattern: given the current progress numbers, return the
     * state the request should now be in. States that never move return $this.
     */
    abstract public function next(RequestProgress $progress): self;

    /** Charity may still edit the request details. */
    public function canEdit(): bool
    {
        return false;
    }

    /** Charity may still withdraw the request. */
    public function canCancel(): bool
    {
        return false;
    }

    /** New donation reservations may still be attached to the request. */
    public function canReserve(): bool
    {
        return false;
    }

    /** No further transition is possible from this state. */
    public function isFinal(): bool
    {
        return false;
    }

    /** Factory method mapping a stored status code onto its state object. */
    public static function for(string $code): self
    {
        return match ($code) {
            self::PENDING => new PendingState(),
            self::PARTIALLY_FULFILLED => new PartiallyFulfilledState(),
            self::COMPLETED => new CompletedState(),
            self::CANCELLED => new CancelledState(),
            self::EXPIRED => new ExpiredState(),
            default => throw new InvalidArgumentException("Unknown request status [$code]."),
        };
    }

    /** All valid status codes; used by validation rules and filters. */
    public static function codes(): array
    {
        return [self::PENDING, self::PARTIALLY_FULFILLED, self::COMPLETED, self::CANCELLED, self::EXPIRED];
    }

    /** Status code => label map for the dashboard filter dropdown. */
    public static function options(): array
    {
        $options = [];
        foreach (self::codes() as $code) {
            $options[$code] = self::for($code)->label();
        }

        return $options;
    }

    public function is(string $code): bool
    {
        return $this->code() === $code;
    }

    public function __toString(): string
    {
        return $this->code();
    }
}
