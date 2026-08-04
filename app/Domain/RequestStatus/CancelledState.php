<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Domain/RequestStatus/CancelledState.php
 * Purpose: Concrete terminal state. The charity withdrew the request, so it is
 *          kept for history only and can no longer be edited or reserved.
 */

namespace App\Domain\RequestStatus;

final class CancelledState extends RequestState
{
    public function code(): string
    {
        return self::CANCELLED;
    }

    public function label(): string
    {
        return 'Cancelled';
    }

    public function badgeClass(): string
    {
        return 'bg-dark';
    }

    public function isFinal(): bool
    {
        return true;
    }

    public function next(RequestProgress $progress): RequestState
    {
        return $this;
    }
}
