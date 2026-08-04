<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Domain/RequestStatus/PartiallyFulfilledState.php
 * Purpose: Concrete state. At least one donation has been reserved or delivered
 *          against the request, so processing has begun and the details are
 *          locked, but the charity may still withdraw the outstanding part.
 */

namespace App\Domain\RequestStatus;

final class PartiallyFulfilledState extends RequestState
{
    public function code(): string
    {
        return self::PARTIALLY_FULFILLED;
    }

    public function label(): string
    {
        return 'Partially Fulfilled';
    }

    public function badgeClass(): string
    {
        return 'bg-warning text-dark';
    }

    public function canCancel(): bool
    {
        return true;
    }

    public function canReserve(): bool
    {
        return true;
    }

    public function next(RequestProgress $progress): RequestState
    {
        if ($progress->isComplete()) {
            return new CompletedState();
        }

        if (! $progress->hasCommitment()) {
            // Every reservation was cancelled again, so processing has not started.
            return $progress->deadlinePassed ? new ExpiredState() : new PendingState();
        }

        return $progress->deadlinePassed ? new ExpiredState() : $this;
    }
}
