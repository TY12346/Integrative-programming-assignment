{{--
FoodLink - Module 4 Delivery & Impact Tracking
Author: KHOO SHENG HAO
--}}
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="mb-1">Delivery & Impact Tracking</h1>
        <p class="text-muted mb-0">Manage delivery tasks and view completed delivery impact.</p>
    </div>
    <a class="btn btn-success" href="{{ route('deliveries.create') }}">Create Delivery Task</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-body h-100">
            <div class="text-muted">Visible Tasks</div>
            <div class="fs-2 fw-bold">{{ $deliveries->count() }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-body h-100">
            <div class="text-muted">Completed Deliveries</div>
            <div class="fs-2 fw-bold">{{ $completedDeliveries }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-body h-100">
            <div class="text-muted">Delivered Food Impact</div>
            @forelse ($impactByUnit as $unit => $quantity)
                <div><strong>{{ number_format($quantity, 2) }}</strong> {{ $unit }}</div>
            @empty
                <div class="text-muted">No completed impact yet.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>ID</th>
                <th>Food</th>
                <th>Volunteer</th>
                <th>Pickup</th>
                <th>Destination</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($deliveries as $delivery)
                <tr>
                    <td>#{{ $delivery->delivery_id }}</td>
                    <td>{{ $delivery->reservation?->donation?->food_name ?? '-' }}</td>
                    <td>{{ $delivery->volunteer?->user?->full_name ?? '-' }}</td>
                    <td>{{ $delivery->pickup_address }}</td>
                    <td>{{ $delivery->delivery_address }}</td>
                    <td><span class="badge {{ $delivery->statusBadgeClass() }}">{{ $delivery->delivery_status }}</span></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-success" href="{{ route('deliveries.show', $delivery) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No delivery tasks found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
