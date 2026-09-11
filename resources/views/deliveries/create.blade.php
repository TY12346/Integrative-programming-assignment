{{--
FoodLink - Module 4 Delivery & Impact Tracking
Author: KHOO SHENG HAO
--}}
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="mb-1">Create Delivery Task</h1>
        <p class="text-muted mb-0">Create a task from an active food reservation.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('deliveries.index') }}">Back</a>
</div>

@if ($reservations->isEmpty())
    <div class="alert alert-info">There are no active reservations waiting for delivery.</div>
@else
<form method="post" action="{{ route('deliveries.store') }}" class="card card-body">
    @csrf

    <div class="mb-3">
        <label class="form-label" for="reservation_id">Reservation</label>
        <select class="form-select" id="reservation_id" name="reservation_id" required>
            <option value="">Select a reservation</option>
            @foreach ($reservations as $reservation)
                <option value="{{ $reservation->reservation_id }}" @selected(old('reservation_id', request('reservation_id')) == $reservation->reservation_id)>
                    #{{ $reservation->reservation_id }} -
                    {{ $reservation->donation->food_name }}
                    ({{ $reservation->reserved_quantity }} {{ $reservation->donation->measurement_unit }})
                    → {{ $reservation->request->charity?->address ?? 'Charity address unavailable' }}
                </option>
            @endforeach
        </select>
    </div>

    @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
        <div class="mb-3">
            <label class="form-label" for="volunteer_id">Assign Volunteer</label>
            <select class="form-select" id="volunteer_id" name="volunteer_id" required>
                <option value="">Select a volunteer</option>
                @foreach ($volunteers as $volunteer)
                    <option value="{{ $volunteer->profile_id }}" @selected(old('volunteer_id') == $volunteer->profile_id)>
                        {{ $volunteer->user->full_name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="mb-3">
        <label class="form-label" for="delivery_notes">Delivery Notes</label>
        <textarea class="form-control" id="delivery_notes" name="delivery_notes" rows="4" maxlength="1000" placeholder="Optional pickup or delivery note">{{ old('delivery_notes') }}</textarea>
        <div class="form-text">Notes are stored as plain text; HTML markup is removed for security.</div>
    </div>

    <button class="btn btn-success" type="submit">Create Delivery Task</button>
</form>
@endif
@endsection
