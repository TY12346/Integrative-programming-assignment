<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Domain/RequestStatus/CompletedState.php
 * Purpose: Concrete terminal state. The requested quantity has been delivered,
 *          which is reported back to this module by the Delivery and Impact
 *          Tracking module (3.4) when it completes a reservation.
 */

namespace App\Domain\RequestStatus;

final class CompletedState extends RequestState
{
    public function code(): string
    {
        return self::COMPLETED;
    }

    public function label(): string
    {
        return 'Completed';
    }

    public function badgeClass(): string
    {
        return 'bg-success';
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
