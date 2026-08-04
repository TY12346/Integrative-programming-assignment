<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Repositories/FoodRequestRepository.php
 * Purpose: REPOSITORY design pattern. All Eloquent queries for food requests
 *          live here, so the controllers stay thin and the same query rules are
 *          reused by the web controller and by the REST web service.
 *
 * Secure coding: every read is scoped to the charity that owns the rows, and
 * the sort column is resolved from a fixed whitelist instead of being taken
 * from the query string, which rules out both broken access control and order
 * by injection.
 */

namespace App\Repositories;

use App\Domain\RequestStatus\RequestState;
use App\Filters\Donation\KeywordFilter;
use App\Models\FoodRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class FoodRequestRepository
{
    /** Allowed sort keys => [column, direction]. */
    private const SORTS = [
        'deadline_asc' => ['request_deadline', 'asc'],
        'deadline_desc' => ['request_deadline', 'desc'],
        'newest' => ['request_id', 'desc'],
        'oldest' => ['request_id', 'asc'],
        'quantity_desc' => ['requested_quantity', 'desc'],
    ];

    public const SCOPE_ACTIVE = 'active';
    public const SCOPE_HISTORY = 'history';
    public const SCOPE_ALL = 'all';

    /**
     * "View Request Dashboard": paginated active or historical requests of one
     * charity, with the reserved quantity already aggregated.
     *
     * @param  array{scope?:string,status?:string,keyword?:string,sort?:string}  $filters
     */
    public function dashboard(int $charityId, array $filters = []): LengthAwarePaginator
    {
        $query = FoodRequest::query()
            ->ownedBy($charityId)
            ->with('category')
            ->withReservedQuantity();

        $this->applyScope($query, $filters['scope'] ?? self::SCOPE_ACTIVE);
        $this->applyStatus($query, $filters['status'] ?? null);
        $this->applyKeyword($query, $filters['keyword'] ?? null);
        $this->applySort($query, $filters['sort'] ?? 'deadline_asc');

        return $query->paginate((int) config('foodlink.request.per_page', 10))->withQueryString();
    }

    /** Counters shown on top of the dashboard. */
    public function summary(int $charityId): array
    {
        $counts = FoodRequest::query()
            ->ownedBy($charityId)
            ->selectRaw('request_status, COUNT(*) AS total')
            ->groupBy('request_status')
            ->pluck('total', 'request_status');

        $summary = ['TOTAL' => (int) $counts->sum()];

        foreach (RequestState::codes() as $code) {
            $summary[$code] = (int) ($counts[$code] ?? 0);
        }

        $summary['URGENT'] = FoodRequest::query()
            ->ownedBy($charityId)
            ->active()
            ->whereBetween('request_deadline', [
                now(),
                now()->addHours((int) config('foodlink.request.urgent_within_hours', 24)),
            ])
            ->count();

        return $summary;
    }

    /**
     * Requests that can still absorb a donation, used when a charity reserves
     * a donation from the browse page.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, FoodRequest>
     */
    public function openRequests(int $charityId)
    {
        return FoodRequest::query()
            ->ownedBy($charityId)
            ->whereIn('request_status', [RequestState::PENDING, RequestState::PARTIALLY_FULFILLED])
            ->with('category')
            ->withReservedQuantity()
            ->orderBy('request_deadline')
            ->get();
    }

    private function applyScope(Builder $query, string $scope): void
    {
        match ($scope) {
            self::SCOPE_HISTORY => $query->historical(),
            self::SCOPE_ALL => null,
            default => $query->active(),
        };
    }

    private function applyStatus(Builder $query, ?string $status): void
    {
        if ($status !== null && in_array($status, RequestState::codes(), true)) {
            $query->where('request_status', $status);
        }
    }

    /** Free text search over the request notes and the food category name. */
    private function applyKeyword(Builder $query, ?string $keyword): void
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return;
        }

        $term = '%'.KeywordFilter::escapeLike($keyword).'%';

        $query->where(function (Builder $inner) use ($term) {
            $inner->where('notes', 'like', $term)
                ->orWhere('unit', 'like', $term)
                ->orWhereHas('category', fn (Builder $c) => $c->where('category_name', 'like', $term));
        });
    }

    private function applySort(Builder $query, string $sort): void
    {
        [$column, $direction] = self::SORTS[$sort] ?? self::SORTS['deadline_asc'];

        $query->orderBy($column, $direction)->orderBy('request_id', 'desc');
    }

    /** Sort keys offered by the dashboard dropdown. */
    public static function sortOptions(): array
    {
        return [
            'deadline_asc' => 'Deadline (soonest first)',
            'deadline_desc' => 'Deadline (latest first)',
            'newest' => 'Newest request',
            'oldest' => 'Oldest request',
            'quantity_desc' => 'Largest quantity',
        ];
    }
}
