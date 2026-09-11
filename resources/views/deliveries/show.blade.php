{{--
FoodLink - Module 4 Delivery & Impact Tracking
Author: KHOO SHENG HAO
--}}
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="mb-1">Delivery #{{ $delivery->delivery_id }}</h1>
        <span class="badge {{ $delivery->statusBadgeClass() }}">{{ $delivery->delivery_status }}</span>
    </div>
    <div class="d-flex gap-2">
        @if (! $delivery->isFinal())
            <a class="btn btn-success" href="{{ route('deliveries.edit', $delivery) }}">Update Status</a>
        @endif
        <a class="btn btn-outline-secondary" href="{{ route('deliveries.index') }}">Back</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card card-body h-100">
            <h5>Task Details</h5>
            <dl class="row mb-0">
                <dt class="col-sm-4">Volunteer</dt><dd class="col-sm-8">{{ $delivery->volunteer?->user?->full_name ?? '-' }}</dd>
                <dt class="col-sm-4">Food</dt><dd class="col-sm-8">{{ $delivery->reservation?->donation?->food_name ?? '-' }}</dd>
                <dt class="col-sm-4">Quantity</dt><dd class="col-sm-8">{{ $delivery->reservation?->reserved_quantity ?? '-' }} {{ $delivery->reservation?->donation?->measurement_unit ?? '' }}</dd>
                <dt class="col-sm-4">Pickup</dt><dd class="col-sm-8">{{ $delivery->pickup_address }}</dd>
                <dt class="col-sm-4">Delivery</dt><dd class="col-sm-8">{{ $delivery->delivery_address }}</dd>
                <dt class="col-sm-4">Assigned</dt><dd class="col-sm-8">{{ $delivery->assigned_at?->format('d M Y H:i') ?? '-' }}</dd>
                <dt class="col-sm-4">Picked Up</dt><dd class="col-sm-8">{{ $delivery->picked_up_at?->format('d M Y H:i') ?? '-' }}</dd>
                <dt class="col-sm-4">Delivered</dt><dd class="col-sm-8">{{ $delivery->delivered_at?->format('d M Y H:i') ?? '-' }}</dd>
            </dl>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card card-body h-100">
            <h5>Delivery Notes</h5>
            {{-- SECURITY: Blade {{ }} applies context-aware HTML output encoding. --}}
            <p class="mb-0" style="white-space: pre-wrap">{{ $delivery->delivery_notes ?: 'No delivery notes.' }}</p>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header fw-semibold">Status History</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Time</th><th>Old</th><th>New</th><th>Changed By</th><th>Remarks</th></tr></thead>
            <tbody>
            @forelse ($delivery->statusHistories->sortByDesc('changed_at') as $history)
                <tr>
                    <td>{{ $history->changed_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td>{{ $history->old_status ?? '-' }}</td>
                    <td>{{ $history->new_status }}</td>
                    <td>{{ $history->changedBy?->full_name ?? '-' }}</td>
                    {{-- SECURITY: stored remarks are rendered with escaped Blade output. --}}
                    <td style="white-space: pre-wrap">{{ $history->remarks ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">No history yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header fw-semibold">Impact Records (Observer Pattern)</div>
    <div class="card-body">
        @forelse ($delivery->impacts as $impact)
            <div class="mb-2">
                <strong>{{ $impact->impact_type }}</strong> —
                {{ $impact->quantity }} {{ $impact->measurement_unit }}
                <span class="text-muted">({{ $impact->recorded_at?->format('d M Y H:i') }})</span>
                <div>{{ $impact->description }}</div>
            </div>
        @empty
            <div class="text-muted">Impact is created automatically when the delivery becomes DELIVERED.</div>
        @endforelse
    </div>
</div>
@endsection
