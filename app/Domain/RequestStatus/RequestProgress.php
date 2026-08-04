<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Domain/RequestStatus/RequestProgress.php
 * Purpose: Immutable value object holding the numbers a request state needs in
 *          order to decide the next state. Keeping it free of Eloquent makes the
 *          whole state machine unit-testable without a database.
 */

namespace App\Domain\RequestStatus;

final class RequestProgress
{
    public function __construct(
        public readonly float $requested,
        public readonly float $fulfilled,
        public readonly float $reserved,
        public readonly bool $deadlinePassed = false,
    ) {
    }

    /** Quantity already delivered plus quantity committed by donors. */
    public function committed(): float
    {
        return $this->fulfilled + $this->reserved;
    }

    /** Quantity still to be matched with a donation. */
    public function outstanding(): float
    {
        return max(0.0, $this->requested - $this->committed());
    }

    /** Delivered quantity has met the requested quantity. */
    public function isComplete(): bool
    {
        return $this->requested > 0 && $this->fulfilled >= $this->requested;
    }

    /** At least one donor has reserved or delivered something. */
    public function hasCommitment(): bool
    {
        return $this->committed() > 0;
    }

    /** Percentage used by the progress bar on the dashboard (0-100). */
    public function percentage(): int
    {
        if ($this->requested <= 0) {
            return 0;
        }

        return (int) min(100, round($this->committed() / $this->requested * 100));
    }
}
