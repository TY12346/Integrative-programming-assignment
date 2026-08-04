<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Domain/RequestStatus/PendingState.php
 * Purpose: Concrete state. The request has been submitted but no donor has
 *          committed anything yet, so the charity may still edit or cancel it.
 */

namespace App\Domain\RequestStatus;

final class PendingState extends RequestState
{
    public function code(): string
    {
        return self::PENDING;
    }

    public function label(): string
    {
        return 'Pending';
    }

    public function badgeClass(): string
    {
        return 'bg-secondary';
    }

    public function canEdit(): bool
    {
        return true;   // "Edit Request Details" is only allowed before processing starts.
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

        if ($progress->hasCommitment()) {
            return new PartiallyFulfilledState();
        }

        return $progress->deadlinePassed ? new ExpiredState() : $this;
    }
}
