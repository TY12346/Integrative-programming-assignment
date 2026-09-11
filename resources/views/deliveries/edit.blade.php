{{--
FoodLink - Module 4 Delivery & Impact Tracking
Author: KHOO SHENG HAO
--}}
@extends('layouts.app')

@section('content')
<h1>Update Delivery #{{ $delivery->delivery_id }}</h1>
<p>Current status: <span class="badge {{ $delivery->statusBadgeClass() }}">{{ $delivery->delivery_status }}</span></p>

@if ($delivery->isFinal())
    <div class="alert alert-info">This delivery is final and cannot be changed further.</div>
    <a class="btn btn-outline-secondary" href="{{ route('deliveries.show', $delivery) }}">Back</a>
@else
<form method="post" action="{{ route('deliveries.update', $delivery) }}" class="card card-body">
    @csrf
    @method('PATCH')

    <div class="mb-3">
        <label class="form-label" for="delivery_status">New Status</label>
        <select class="form-select" id="delivery_status" name="delivery_status" required>
            <option value="">Select the next status</option>
            @foreach ($delivery->allowedNextStatuses() as $status)
                <option value="{{ $status }}" @selected(old('delivery_status') === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label" for="remarks">Delivery Notes / Remarks</label>
        <textarea class="form-control" id="remarks" name="remarks" rows="4" maxlength="1000">{{ old('remarks', $delivery->delivery_notes) }}</textarea>
        <div class="form-text">HTML is removed before storage and encoded again when displayed.</div>
    </div>

    <button class="btn btn-success" type="submit">Update Delivery</button>
    <a class="btn btn-outline-secondary" href="{{ route('deliveries.show', $delivery) }}">Cancel</a>
</form>
@endif
@endsection
