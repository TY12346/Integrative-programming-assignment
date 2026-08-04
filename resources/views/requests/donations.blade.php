{{--
    FoodLink - Module 3.3 Food Request Management
    Author : NG JIA QIN
    File   : resources/views/requests/donations.blade.php
    Purpose: Function 8 "Display Active Donations", function 9 "Filter Donation
             Options" and function 10 "Search Specific Donations", plus the
             reserve action that feeds function 5 "Track Reserved Quantity".

             The donation data shown here is owned by the Food Donation
             Management module (3.2) and is read through DonationGateway, so
             this page works whether the two modules share a database or talk
             over the REST web service.
--}}
@extends('layouts.app')

@section('content')
    @php($selected = $selectedRequestId ? $openRequests->firstWhere('request_id', $selectedRequestId) : null)

    <h1 class="h3 mb-1">Active donations</h1>
    <p class="text-muted">Search the food that donors have posted, then reserve it against one of your open requests.</p>

    {{-- Search + filter + target request (single GET form, values echoed back escaped) --}}
    <form class="card mb-3" method="get" action="{{ route('requests.donations') }}">
        <div class="card-body row g-2">
            <div class="col-md-4">
                <label class="form-label" for="keyword">Search</label>
                <input class="form-control" id="keyword" type="search" name="keyword" maxlength="60"
                       value="{{ $criteria['keyword'] ?? '' }}" placeholder="Food name or description">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="category_id">Food category</label>
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
            <div class="col-md-2">
                <label class="form-label" for="storage_type">Storage</label>
                <select class="form-select" id="storage_type" name="storage_type">
                    <option value="">Any</option>
                    @foreach ($storageTypes as $storage)
                        <option value="{{ $storage }}" @selected(($criteria['storage_type'] ?? '') === $storage)>{{ $storage }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label" for="min_quantity">Min qty</label>
                <input class="form-control" id="min_quantity" type="number" step="0.01" min="0"
                       name="min_quantity" value="{{ $criteria['min_quantity'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="expires_within_hours">Expiring within</label>
                <select class="form-select" id="expires_within_hours" name="expires_within_hours">
                    <option value="">Any time</option>
                    @foreach ($expiryWindows as $hours)
                        <option value="{{ $hours }}" @selected((int) ($criteria['expires_within_hours'] ?? 0) === $hours)>{{ $hours }} hours</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="request_id">Reserve for which request?</label>
                <select class="form-select" id="request_id" name="request_id">
                    <option value="">-- choose one of my open requests --</option>
                    @foreach ($openRequests as $openRequest)
                        <option value="{{ $openRequest->request_id }}" @selected($selectedRequestId === $openRequest->request_id)>
                            #{{ $openRequest->request_id }} ·
                            {{ $openRequest->category->category_name ?? 'Uncategorised' }} ·
                            still needs {{ round($openRequest->progress()->outstanding(), 2) }} {{ $openRequest->unit }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-end gap-2">
                <button class="btn btn-success">Apply</button>
                <a class="btn btn-outline-secondary" href="{{ route('requests.donations') }}">Reset</a>
            </div>
        </div>
    </form>

    @if ($openRequests->isEmpty())
        <div class="alert alert-warning">
            You have no open food request yet.
            <a href="{{ route('requests.create') }}">Create one</a> before reserving donations.
        </div>
    @elseif (! $selected)
        <div class="alert alert-info">Choose one of your open requests above to enable the reserve buttons.</div>
    @endif

    <p class="text-muted">{{ $donations->count() }} active donation(s) found.</p>

    <div class="row g-3">
        @forelse ($donations as $donation)
            @php($maxQuantity = $selected
                ? min((float) $donation->current_quantity, $selected->progress()->outstanding())
                : (float) $donation->current_quantity)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title mb-1">{{ $donation->food_name }}</h5>
                            <span class="badge bg-light text-dark">{{ $donation->category->category_name ?? '' }}</span>
                        </div>
                        <p class="card-text small text-muted mb-2">{{ Str::limit($donation->description, 90) }}</p>
                        <ul class="list-unstyled small mb-3">
                            <li><strong>{{ $donation->current_quantity }} {{ $donation->measurement_unit }}</strong> available</li>
                            <li>Expires {{ $donation->expiry_datetime?->format('d M Y H:i') }}</li>
                            <li>Pickup: {{ $donation->pickup_address }}</li>
                            @if ($donation->storage_type)<li>Storage: {{ $donation->storage_type }}</li>@endif
                            @if ($donation->halal_status)<li>Halal status: {{ $donation->halal_status }}</li>@endif
                        </ul>

                        @if ($selected && $maxQuantity > 0)
                            <form method="post" action="{{ route('requests.reserve', $selected->request_id) }}" class="row g-2">
                                @csrf
                                <input type="hidden" name="donation_id" value="{{ $donation->donation_id }}">
                                <div class="col-5">
                                    <input class="form-control form-control-sm" type="number" step="0.01"
                                           min="0.01" max="{{ $maxQuantity }}" name="reserved_quantity"
                                           value="{{ $maxQuantity }}" required aria-label="Quantity to reserve">
                                </div>
                                <div class="col-7">
                                    <input class="form-control form-control-sm" type="datetime-local"
                                           name="pickup_deadline" required aria-label="Pickup deadline"
                                           value="{{ $donation->expiry_datetime?->format('Y-m-d\TH:i') }}"
                                           max="{{ $donation->expiry_datetime?->format('Y-m-d\TH:i') }}">
                                </div>
                                <div class="col-12 d-grid">
                                    <button class="btn btn-sm btn-success">Reserve for request #{{ $selected->request_id }}</button>
                                </div>
                            </form>
                        @elseif ($selected)
                            <div class="alert alert-secondary py-1 mb-0 small">
                                This request no longer needs more food.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-secondary">No active donation matches your search.</div>
            </div>
        @endforelse
    </div>
@endsection
