<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Domain/RequestStatus/ExpiredState.php
 * Purpose: Concrete state supporting "Monitor Fulfillment Deadline". The
 *          fulfilment deadline passed before the requested quantity was
 *          delivered. A late delivery can still complete the request, so this
 *          state is not final.
 */

namespace App\Domain\RequestStatus;

final class ExpiredState extends RequestState
{
    public function code(): string
    {
        return self::EXPIRED;
    }

    public function label(): string
    {
        return 'Expired';
    }

    public function badgeClass(): string
    {
        return 'bg-danger';
    }

    public function canCancel(): bool
    {
        return true;   // The charity can tidy up an expired request.
    }

    public function next(RequestProgress $progress): RequestState
    {
        if ($progress->isComplete()) {
            return new CompletedState();
        }

        return $progress->deadlinePassed ? $this : new PendingState();
    }
}
