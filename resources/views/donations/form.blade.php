@extends('layouts.app')

@section('error_summary')
    Please correct the errors below.
@endsection

@section('content')
    <h1 class="h3 mb-3">{{ $donation->exists ? 'Edit' : 'Create' }} Donation</h1>

    <form
        method="post"
        enctype="multipart/form-data"
        action="{{ $donation->exists ? '/donations/'.$donation->donation_id : '/donations' }}"
        class="mb-3"
        novalidate
    >
        @csrf
        @if ($donation->exists)
            @method('PUT')
        @endif

        <div class="mb-3">
            <label class="form-label" for="category_id">Category</label>
            <select
                class="form-select @error('category_id') is-invalid @enderror"
                id="category_id"
                name="category_id"
                required
                aria-describedby="@error('category_id') category_id_error @enderror"
            >
                <option value="">Select a category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->category_id }}"
                        @selected((string) old('category_id', $donation->category_id) === (string) $category->category_id)>
                        {{ $category->category_name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')
                <div id="category_id_error" class="invalid-feedback" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="food_name">Food name</label>
            <input
                class="form-control @error('food_name') is-invalid @enderror"
                id="food_name"
                name="food_name"
                maxlength="255"
                required
                value="{{ old('food_name', $donation->food_name) }}"
                placeholder="Food name"
                aria-describedby="@error('food_name') food_name_error @enderror"
            >
            @error('food_name')
                <div id="food_name_error" class="invalid-feedback" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="description">Description</label>
            <textarea
                class="form-control @error('description') is-invalid @enderror"
                id="description"
                name="description"
                maxlength="5000"
                placeholder="Description"
                aria-describedby="@error('description') description_error @enderror"
            >{{ old('description', $donation->description) }}</textarea>
            @error('description')
                <div id="description_error" class="invalid-feedback" role="alert">{{ $message }}</div>
            @enderror
        </div>

        @if ($donation->exists)
            <div class="mb-3">
                <label class="form-label">Original quantity</label>
                <p class="form-control-plaintext mb-1">
                    {{ $donation->donation_quantity }} {{ $donation->measurement_unit }}
                </p>
                <p class="form-text text-muted mb-0">
                    Remaining available quantity (not editable):
                    <strong>{{ $donation->current_quantity }} {{ $donation->measurement_unit }}</strong>.
                    Status: <strong>{{ $donation->statusLabel() }}</strong>.
                </p>
            </div>
        @else
            <div class="mb-3">
                <label class="form-label" for="donation_quantity">Original quantity</label>
                <input
                    class="form-control @error('donation_quantity') is-invalid @enderror"
                    id="donation_quantity"
                    name="donation_quantity"
                    type="number"
                    step="0.01"
                    min="0.01"
                    required
                    value="{{ old('donation_quantity', $donation->donation_quantity) }}"
                    placeholder="Quantity"
                    aria-describedby="@error('donation_quantity') donation_quantity_error @enderror"
                >
                @error('donation_quantity')
                    <div id="donation_quantity_error" class="invalid-feedback" role="alert">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="measurement_unit">Unit</label>
                <select
                    class="form-select @error('measurement_unit') is-invalid @enderror"
                    id="measurement_unit"
                    name="measurement_unit"
                    required
                    aria-describedby="@error('measurement_unit') measurement_unit_error @enderror"
                >
                    <option value="">Select a unit</option>
                    @foreach (config('foodlink.request.units', []) as $unit)
                        <option value="{{ $unit }}"
                            @selected(old('measurement_unit', $donation->measurement_unit) === $unit)>
                            {{ $unit }}
                        </option>
                    @endforeach
                </select>
                @error('measurement_unit')
                    <div id="measurement_unit_error" class="invalid-feedback" role="alert">{{ $message }}</div>
                @enderror
            </div>
        @endif

        <div class="mb-3">
            <label class="form-label" for="expiry_datetime">Expiry date/time</label>
            <input
                class="form-control @error('expiry_datetime') is-invalid @enderror"
                id="expiry_datetime"
                type="datetime-local"
                name="expiry_datetime"
                required
                value="{{ old(
                    'expiry_datetime',
                    $donation->expiry_datetime ? $donation->expiry_datetime->format('Y-m-d\TH:i') : ''
                ) }}"
                aria-describedby="@error('expiry_datetime') expiry_datetime_error @enderror"
            >
            @error('expiry_datetime')
                <div id="expiry_datetime_error" class="invalid-feedback" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="pickup_address">Pickup address</label>
            <input
                class="form-control @error('pickup_address') is-invalid @enderror"
                id="pickup_address"
                name="pickup_address"
                maxlength="1000"
                required
                value="{{ old('pickup_address', $donation->pickup_address) }}"
                placeholder="Pickup address"
                aria-describedby="@error('pickup_address') pickup_address_error @enderror"
            >
            @error('pickup_address')
                <div id="pickup_address_error" class="invalid-feedback" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="storage_type">Storage requirement</label>
            <input
                class="form-control @error('storage_type') is-invalid @enderror"
                id="storage_type"
                name="storage_type"
                maxlength="255"
                value="{{ old('storage_type', $donation->storage_type) }}"
                placeholder="Storage type"
                aria-describedby="@error('storage_type') storage_type_error @enderror"
            >
            @error('storage_type')
                <div id="storage_type_error" class="invalid-feedback" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="halal_status">Halal status</label>
            <select
                class="form-select @error('halal_status') is-invalid @enderror"
                id="halal_status"
                name="halal_status"
                aria-describedby="@error('halal_status') halal_status_error @enderror"
            >
                <option value="">Select Halal Status</option>
                <option value="Halal" @selected(old('halal_status', $donation->halal_status) === 'Halal')>Halal</option>
                <option value="Non-Halal" @selected(old('halal_status', $donation->halal_status) === 'Non-Halal')>Non-Halal</option>
            </select>
            @error('halal_status')
                <div id="halal_status_error" class="invalid-feedback" role="alert">{{ $message }}</div>
            @enderror
        </div>

        @unless ($donation->exists)
            <div class="mb-3">
                <label class="form-label" for="photos">Photos (optional)</label>
                <input
                    class="form-control @error('photos') is-invalid @enderror @error('photos.0') is-invalid @enderror"
                    id="photos"
                    type="file"
                    name="photos[]"
                    accept=".jpeg,.jpg,.png,.webp,image/jpeg,image/png,image/webp"
                    multiple
                    aria-describedby="@error('photos') photos_error @enderror @error('photos.*') photos_item_error @enderror"
                >
                @error('photos')
                    <div id="photos_error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                @enderror
                @error('photos.*')
                    <div id="photos_item_error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                @enderror
                <p class="form-text text-muted">Accepted formats: JPEG, JPG, PNG, WebP. Maximum size: 5 MB each.</p>
            </div>
        @endunless

        <button class="btn btn-success" type="submit">Save</button>
        <a class="btn btn-outline-secondary" href="/donations">Back</a>
    </form>

    @if ($donation->exists)
        <div class="card mb-3">
            <div class="card-header">Photos</div>
            <div class="card-body">
                @if ($donation->photos->isEmpty())
                    <p class="text-muted">No photos available</p>
                @else
                    <div class="row g-3 mb-3">
                        @foreach ($donation->photos as $photo)
                            <div class="col-6 col-md-4 col-lg-3">
                                <img
                                    class="img-fluid rounded border mb-2"
                                    src="{{ url('/donations/photos/'.$photo->photo_id.'/file') }}"
                                    alt="Donation photo {{ $photo->photo_id }}"
                                >
                                <form
                                    method="post"
                                    action="/donations/{{ $donation->donation_id }}/photos/{{ $photo->photo_id }}"
                                    onsubmit="return confirm('Delete this photo?');"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">Delete</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif

                <hr>
                <h2 class="h6">Upload photos</h2>
                <form method="post" action="/donations/{{ $donation->donation_id }}/photos" enctype="multipart/form-data">
                    @csrf
                    <input
                        class="form-control mb-2 @error('photos') is-invalid @enderror @error('photos.0') is-invalid @enderror"
                        type="file"
                        name="photos[]"
                        accept=".jpeg,.jpg,.png,.webp,image/jpeg,image/png,image/webp"
                        multiple
                        required
                    >
                    @error('photos')
                        <div class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                    @enderror
                    @error('photos.*')
                        <div class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                    @enderror
                    <p class="form-text text-muted">Accepted formats: JPEG, JPG, PNG, WebP. Maximum size: 5 MB each.</p>
                    <button type="submit" class="btn btn-success">Upload</button>
                </form>
            </div>
        </div>
    @endif

    <script>
        (function () {
            var quantityInput = document.getElementById('donation_quantity');
            var unitSelect = document.getElementById('measurement_unit');

            if (!quantityInput || !unitSelect) {
                return;
            }

            var decimalUnits = ['kg', 'g', 'litre'];

            function applyQuantityHints() {
                var unit = unitSelect.value;

                if (decimalUnits.indexOf(unit) !== -1) {
                    quantityInput.step = '0.01';
                    quantityInput.placeholder = 'e.g. 2.5';
                } else if (unit) {
                    quantityInput.step = '1';
                    quantityInput.placeholder = 'e.g. 10';
                } else {
                    quantityInput.step = '0.01';
                    quantityInput.placeholder = 'Quantity';
                }
            }

            unitSelect.addEventListener('change', applyQuantityHints);
            applyQuantityHints();
        })();
    </script>
@endsection
