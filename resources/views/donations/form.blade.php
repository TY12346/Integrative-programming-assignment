@extends('layouts.app')

@section('content')
    <h1 class="h3 mb-3">{{ $donation->exists ? 'Edit' : 'Create' }} Donation</h1>

    <form
        method="post"
        enctype="multipart/form-data"
        action="{{ $donation->exists ? '/donations/'.$donation->donation_id : '/donations' }}"
        class="mb-3"
    >
        @csrf
        @if ($donation->exists)
            @method('PUT')
        @endif

        <label class="form-label" for="category_id">Category</label>
        <select class="form-select mb-2" id="category_id" name="category_id" required>
            <option value="">Select a category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->category_id }}"
                    @selected((string) old('category_id', $donation->category_id) === (string) $category->category_id)>
                    {{ $category->category_name }}
                </option>
            @endforeach
        </select>

        <label class="form-label" for="food_name">Food name</label>
        <input class="form-control mb-2" id="food_name" name="food_name" maxlength="255" required
               value="{{ old('food_name', $donation->food_name) }}" placeholder="Food name">

        <label class="form-label" for="description">Description</label>
        <textarea class="form-control mb-2" id="description" name="description" maxlength="5000"
                  placeholder="Description">{{ old('description', $donation->description) }}</textarea>

        <label class="form-label" for="donation_quantity">Original quantity</label>
        <input class="form-control mb-2" id="donation_quantity" name="donation_quantity" type="number"
               step="0.01" min="0.01" required
               value="{{ old('donation_quantity', $donation->donation_quantity) }}" placeholder="Quantity">
        @if ($donation->exists)
            <p class="form-text text-muted">
                Remaining available quantity (not editable):
                <strong>{{ $donation->current_quantity }} {{ $donation->measurement_unit }}</strong>.
                Status: <strong>{{ $donation->statusLabel() }}</strong>.
            </p>
        @endif

        <label class="form-label" for="measurement_unit">Unit</label>
        <select class="form-select mb-2" id="measurement_unit" name="measurement_unit" required>
            <option value="">Select a unit</option>
            @foreach (config('foodlink.request.units', []) as $unit)
                <option value="{{ $unit }}"
                    @selected(old('measurement_unit', $donation->measurement_unit) === $unit)>
                    {{ $unit }}
                </option>
            @endforeach
        </select>

        <label class="form-label" for="expiry_datetime">Expiry date/time</label>
        <input class="form-control mb-2" id="expiry_datetime" type="datetime-local" name="expiry_datetime" required
               value="{{ old(
                   'expiry_datetime',
                   $donation->expiry_datetime ? $donation->expiry_datetime->format('Y-m-d\TH:i') : ''
               ) }}">

        <label class="form-label" for="pickup_address">Pickup address</label>
        <input class="form-control mb-2" id="pickup_address" name="pickup_address" maxlength="1000" required
               value="{{ old('pickup_address', $donation->pickup_address) }}" placeholder="Pickup address">

        <label class="form-label" for="storage_type">Storage requirement</label>
        <input class="form-control mb-2" id="storage_type" name="storage_type" maxlength="255"
               value="{{ old('storage_type', $donation->storage_type) }}" placeholder="Storage type">

        <label class="form-label" for="halal_status">Halal status</label>
        <input class="form-control mb-2" id="halal_status" name="halal_status" maxlength="255"
               value="{{ old('halal_status', $donation->halal_status) }}" placeholder="Halal status">

        @unless ($donation->exists)
            <label class="form-label" for="photo">Photo (optional)</label>
            <input class="form-control mb-2" id="photo" type="file" name="photo"
                   accept=".jpeg,.jpg,.png,.webp,image/jpeg,image/png,image/webp">
        @endunless

        <button class="btn btn-success" type="submit">Save</button>
        <a class="btn btn-outline-secondary" href="/donations">Back</a>
    </form>
@endsection
