<?php


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
