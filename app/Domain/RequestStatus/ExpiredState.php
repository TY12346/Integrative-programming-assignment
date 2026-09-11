<?php
/**
 * FoodLink - Module 3.3 Food Request Management
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
