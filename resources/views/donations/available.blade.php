@extends('layouts.app')

@section('content')
    @php
        $activeFilterCount = 0;
        if (! empty($criteria['keyword'])) {
            $activeFilterCount++;
        }
        if (! empty($criteria['location'])) {
            $activeFilterCount++;
        }
        if (! empty($criteria['category_id'])) {
            $activeFilterCount++;
        }
        if (isset($criteria['min_quantity']) && $criteria['min_quantity'] !== '' && $criteria['min_quantity'] !== null) {
            $activeFilterCount++;
        }
        if (! empty($criteria['expires_within_hours'])) {
            $activeFilterCount++;
        }
        if (! empty($criteria['storage_type'])) {
            $activeFilterCount++;
        }
        // Default AVAILABLE is empty selection; only count an explicit non-default status.
        if (! empty($criteria['donation_status']) && $criteria['donation_status'] !== 'AVAILABLE') {
            $activeFilterCount++;
        }

        $panelFiltersActive = ! empty($criteria['category_id'])
            || (isset($criteria['min_quantity']) && $criteria['min_quantity'] !== '' && $criteria['min_quantity'] !== null)
            || ! empty($criteria['expires_within_hours'])
            || ! empty($criteria['storage_type'])
            || (! empty($criteria['donation_status']) && $criteria['donation_status'] !== 'AVAILABLE');

        $filtersOpen = $panelFiltersActive;
    @endphp

    <div class="mb-3">
        <h1 class="h3 mb-1">Available Donations</h1>
        <p class="text-muted mb-0">Browse food that donors have posted. Use search and filters to find what you need.</p>
    </div>

    <form method="get" action="/donations/available" class="mb-4">
        {{-- Compact search row --}}
        <div class="row g-2 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label visually-hidden" for="keyword">Food name or description</label>
                <input
                    class="form-control"
                    id="keyword"
                    type="search"
                    name="keyword"
                    maxlength="60"
                    value="{{ $criteria['keyword'] ?? '' }}"
                    placeholder="Food name or description"
                >
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label visually-hidden" for="location">Pickup location</label>
                <input
                    class="form-control"
                    id="location"
                    type="text"
                    name="location"
                    maxlength="255"
                    value="{{ $criteria['location'] ?? '' }}"
                    placeholder="Pickup location"
                >
            </div>
            <div class="col-lg-auto col-md-6">
                <button type="submit" class="btn btn-success w-100">Search Donations</button>
            </div>
            <div class="col-lg-auto col-md-6">
                <button
                    class="btn btn-outline-success w-100 d-inline-flex align-items-center justify-content-center gap-2"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#donationFilterPanel"
                    aria-expanded="{{ $filtersOpen ? 'true' : 'false' }}"
                    aria-controls="donationFilterPanel"
                    id="donationFilterToggle"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .4.8L10 7.5V14a.5.5 0 0 1-.74.44l-3-1.5A.5.5 0 0 1 6 12.5V7.5L1.6 1.8a.5.5 0 0 1-.1-.3z"/>
                    </svg>
                    <span>Filters</span>
                    @if ($activeFilterCount > 0)
                        <span class="badge text-bg-success rounded-pill">{{ $activeFilterCount }}</span>
                    @endif
                    <span class="donation-filter-chevron" aria-hidden="true">▼</span>
                </button>
            </div>
        </div>

        {{-- Collapsible filter panel --}}
        <div class="collapse {{ $filtersOpen ? 'show' : '' }} mt-3" id="donationFilterPanel">
            <div class="border rounded-3 p-3 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 mb-0">Filters</h2>
                    @if ($activeFilterCount > 0)
                        <a class="small text-decoration-none" href="/donations/available">Clear all filters</a>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="category_id">Category</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->category_id }}"
                                    @selected((int) ($criteria['category_id'] ?? 0) === (int) $category->category_id)>
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="min_quantity">Minimum quantity</label>
                        <input
                            class="form-control"
                            id="min_quantity"
                            type="number"
                            step="0.01"
                            min="0"
                            name="min_quantity"
                            value="{{ $criteria['min_quantity'] ?? '' }}"
                            placeholder="Any amount"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="expires_within_hours">Expiring within</label>
                        <select class="form-select" id="expires_within_hours" name="expires_within_hours">
                            <option value="">Any time</option>
                            @foreach ($expiryWindows as $hours)
                                <option value="{{ $hours }}"
                                    @selected((int) ($criteria['expires_within_hours'] ?? 0) === (int) $hours)>
                                    {{ $hours }} hours
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="donation_status">Donation status</label>
                        <select class="form-select" id="donation_status" name="donation_status">
                            <option value="">Available (default)</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}"
                                    @selected(($criteria['donation_status'] ?? '') === $status)>
                                    {{ match ($status) {
                                        'AVAILABLE' => 'Available',
                                        'RESERVED' => 'Reserved',
                                        'COMPLETED' => 'Collected',
                                        'CANCELLED' => 'Cancelled',
                                        'EXPIRED' => 'Expired',
                                        default => $status,
                                    } }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="storage_type">Storage requirement</label>
                        <select class="form-select" id="storage_type" name="storage_type">
                            <option value="">Any</option>
                            @foreach ($storageTypes as $storage)
                                <option value="{{ $storage }}"
                                    @selected(($criteria['storage_type'] ?? '') === $storage)>
                                    {{ $storage }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <hr class="mb-4">

    @if ($donations->isEmpty())
        <div class="border rounded-3 bg-light p-4 text-center">
            <h2 class="h5 mb-2">No donations found</h2>
            <p class="text-muted mb-3">Try adjusting your search or clearing some filters.</p>
            <a class="btn btn-outline-success btn-sm" href="/donations/available">Clear Filters</a>
        </div>
    @else
        <p class="text-muted small mb-3">
            Showing {{ $donations->count() }} donation{{ $donations->count() === 1 ? '' : 's' }}
            @if ($activeFilterCount > 0)
                matching your search
            @endif
        </p>

        <div class="row g-3">
            @foreach ($donations as $donation)
                <div class="col-12">
                    <div class="border rounded-3 p-3 bg-white">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-3">
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <h2 class="h5 mb-0">{{ $donation->food_name }}</h2>
                                    <span class="badge {{ $donation->statusBadgeClass() }}">{{ $donation->statusLabel() }}</span>
                                </div>
                                <p class="text-muted small mb-2">{{ $donation->category->category_name ?? 'Uncategorised' }}</p>
                                <p class="mb-1">
                                    Remaining:
                                    <strong>{{ $donation->current_quantity }} {{ $donation->measurement_unit }}</strong>
                                    <span class="text-muted">(original {{ $donation->donation_quantity }} {{ $donation->measurement_unit }})</span>
                                </p>
                                <p class="mb-1 text-muted">
                                    Expires {{ $donation->expiry_datetime?->format('d M Y H:i') ?? '—' }}
                                    · Storage: {{ $donation->storage_type ?: '—' }}
                                </p>
                                <p class="mb-0">Pickup: {{ $donation->pickup_address }}</p>
                            </div>
                            @if (auth()->user()->role === 'FOOD_DONOR'
                                && (int) (auth()->user()->partnerProfile?->profile_id) === (int) $donation->donor_id)
                                <a class="btn btn-sm btn-outline-success flex-shrink-0" href="/donations/{{ $donation->donation_id }}">View</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Bootstrap JS for collapse only; CSS is already loaded in the layout. --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .donation-filter-chevron {
            display: inline-block;
            font-size: 0.65rem;
            transition: transform 0.2s ease;
        }
        #donationFilterToggle[aria-expanded="true"] .donation-filter-chevron {
            transform: rotate(180deg);
        }
        #donationFilterToggle[aria-expanded="true"] {
            background-color: var(--bs-success);
            color: #fff;
            border-color: var(--bs-success);
        }
        #donationFilterToggle[aria-expanded="true"] .badge {
            background-color: #fff !important;
            color: var(--bs-success) !important;
        }
    </style>
    <script>
        (function () {
            var toggle = document.getElementById('donationFilterToggle');
            var panel = document.getElementById('donationFilterPanel');
            if (!toggle || !panel || !window.bootstrap) {
                return;
            }
            panel.addEventListener('shown.bs.collapse', function () {
                toggle.setAttribute('aria-expanded', 'true');
            });
            panel.addEventListener('hidden.bs.collapse', function () {
                toggle.setAttribute('aria-expanded', 'false');
            });
        })();
    </script>
@endsection
