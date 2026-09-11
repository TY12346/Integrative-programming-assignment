<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 */

namespace App\Services\Gateways;

use App\Models\FoodDonation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final class HttpDonationGateway implements DonationGateway
{
    /** Refuse payloads larger than this (bytes). */
    private const MAX_RESPONSE_BYTES = 1048576;

    public function __construct(
        private readonly LocalDonationGateway $fallback,
        private readonly string $baseUrl,
        private readonly int $timeout = 5,
    ) {
    }

    public function activeDonations(array $criteria = []): Collection
    {
        // The donation service only understands category_id today, so the
        // remaining criteria are applied here after the payload is adapted.
        $payload = $this->get('/api/donations/available', array_filter([
            'category_id' => $criteria['category_id'] ?? null,
        ]));

        if ($payload === null) {
            return $this->fallback->activeDonations($criteria);
        }

        return $this->applyRemainingCriteria($this->hydrate($payload), $criteria);
    }

    public function find(int $donationId): ?FoodDonation
    {
        return $this->activeDonations()
            ->firstWhere('donation_id', $donationId)
            ?? $this->fallback->find($donationId);
    }

    /**
     * Adapt the {status, timestamp, data} envelope used by the FoodLink web
     * services into FoodDonation instances. The models are not saved; they only
     * carry the remote data into the Blade views.
     *
     * @return Collection<int, FoodDonation>
     */
    private function hydrate(array $payload): Collection
    {
        $rows = $payload['data'] ?? [];

        if (! is_array($rows)) {
            return collect();
        }

        return collect($rows)
            ->filter(fn ($row) => is_array($row) && isset($row['donation_id']))
            ->map(fn (array $row) => (new FoodDonation())->forceFill($row)->syncOriginal())
            ->values();
    }

    /** Criteria the remote endpoint cannot express are applied in memory. */
    private function applyRemainingCriteria(Collection $donations, array $criteria): Collection
    {
        if (! empty($criteria['keyword'])) {
            $keyword = mb_strtolower(trim((string) $criteria['keyword']));
            $donations = $donations->filter(fn (FoodDonation $d) => str_contains(mb_strtolower((string) $d->food_name), $keyword)
                || str_contains(mb_strtolower((string) $d->description), $keyword));
        }

        if (! empty($criteria['storage_type'])) {
            $donations = $donations->where('storage_type', $criteria['storage_type']);
        }

        if (! empty($criteria['min_quantity']) && is_numeric($criteria['min_quantity'])) {
            $donations = $donations->filter(fn (FoodDonation $d) => (float) $d->current_quantity >= (float) $criteria['min_quantity']);
        }

        if (! empty($criteria['expires_within_hours']) && is_numeric($criteria['expires_within_hours'])) {
            $limit = now()->addHours((int) $criteria['expires_within_hours']);
            $donations = $donations->filter(fn (FoodDonation $d) => $d->expiry_datetime !== null
                && $d->expiry_datetime->lessThanOrEqualTo($limit));
        }

        return $donations->sortBy('expiry_datetime')->values();
    }

    /** Perform the GET call; returns the decoded body or null on any failure. */
    private function get(string $path, array $query = []): ?array
    {
        $url = rtrim($this->baseUrl, '/').$path.($query ? '?'.http_build_query($query) : '');

        $handle = curl_init($url);

        if ($handle === false) {
            return null;
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($body === false || $status < 200 || $status >= 300) {
            Log::warning('FoodLink donation web service call failed.', [
                'url' => $url, 'status' => $status, 'error' => $error,
            ]);

            return null;
        }

        if (strlen($body) > self::MAX_RESPONSE_BYTES) {
            Log::warning('FoodLink donation web service returned an oversized payload.', ['url' => $url]);

            return null;
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }
}
