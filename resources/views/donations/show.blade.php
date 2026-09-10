@extends('layouts.app')

@section('content')
    @php($canManage = (int) (auth()->user()->partnerProfile?->profile_id) === (int) $donation->donor_id)

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h3 mb-1">{{ $donation->food_name }}</h1>
            <span class="badge {{ $donation->statusBadgeClass() }}">{{ $donation->statusLabel() }}</span>
        </div>
        <a class="btn btn-outline-secondary" href="/donations">Back</a>
    </div>

    <div class="card mb-3">
        <div class="card-header">Quantity tracking</div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="h4 mb-0">{{ $donation->donation_quantity }} {{ $donation->measurement_unit }}</div>
                    <small class="text-muted">Original quantity donated</small>
                </div>
                <div class="col-md-4">
                    <div class="h4 mb-0">{{ $donation->current_quantity }} {{ $donation->measurement_unit }}</div>
                    <small class="text-muted">Remaining available quantity</small>
                </div>
                <div class="col-md-4">
                    <div class="h4 mb-0">
                        <span class="badge {{ $donation->statusBadgeClass() }}">{{ $donation->statusLabel() }}</span>
                    </div>
                    <small class="text-muted">Current status</small>
                </div>
            </div>
            <hr class="my-3">
            <p class="mb-0 text-muted small">
                Remaining quantity is reduced when charities reserve food and restored if a reservation is withdrawn.
                Donors cannot change remaining quantity or status from the edit form.
            </p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Donation details</div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Food name</dt>
                <dd class="col-sm-8">{{ $donation->food_name }}</dd>

                <dt class="col-sm-4">Description</dt>
                <dd class="col-sm-8">{{ $donation->description ?: '—' }}</dd>

                <dt class="col-sm-4">Category</dt>
                <dd class="col-sm-8">{{ $donation->category->category_name ?? '—' }}</dd>

                <dt class="col-sm-4">Original quantity</dt>
                <dd class="col-sm-8">{{ $donation->donation_quantity }} {{ $donation->measurement_unit }}</dd>

                <dt class="col-sm-4">Remaining available quantity</dt>
                <dd class="col-sm-8">{{ $donation->current_quantity }} {{ $donation->measurement_unit }}</dd>

                <dt class="col-sm-4">Measurement unit</dt>
                <dd class="col-sm-8">{{ $donation->measurement_unit }}</dd>

                <dt class="col-sm-4">Expiry date/time</dt>
                <dd class="col-sm-8">{{ $donation->expiry_datetime?->format('d M Y H:i') ?? '—' }}</dd>

                <dt class="col-sm-4">Pickup address</dt>
                <dd class="col-sm-8">{{ $donation->pickup_address }}</dd>

                <dt class="col-sm-4">Storage requirement</dt>
                <dd class="col-sm-8">{{ $donation->storage_type ?: '—' }}</dd>

                <dt class="col-sm-4">Halal status</dt>
                <dd class="col-sm-8">{{ $donation->halal_status ?: '—' }}</dd>

                <dt class="col-sm-4">Donation status</dt>
                <dd class="col-sm-8">
                    <span class="badge {{ $donation->statusBadgeClass() }}">{{ $donation->statusLabel() }}</span>
                </dd>

                <dt class="col-sm-4">Donation date/time</dt>
                <dd class="col-sm-8">{{ $donation->donation_datetime ?: '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Status history</div>
        <div class="card-body">
            @if ($donation->statusHistories->isEmpty())
                <p class="text-muted mb-0">No status history recorded yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>From</th>
                                <th>To</th>
                                <th>Changed by</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($donation->statusHistories->sortByDesc('donation_history_id') as $history)
                                <tr>
                                    <td>{{ $history->old_status ?: '—' }}</td>
                                    <td>{{ $history->new_status }}</td>
                                    <td>{{ $history->changedBy->full_name ?? '—' }}</td>
                                    <td>{{ $history->remarks ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

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
                                src="{{ asset('storage/'.$photo->file_path) }}"
                                alt="Donation photo {{ $photo->photo_id }}"
                            >
                            @if ($canManage)
                                <form
                                    method="post"
                                    action="/donations/{{ $donation->donation_id }}/photos/{{ $photo->photo_id }}"
                                    onsubmit="return confirm('Delete this photo?');"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">Delete</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($canManage)
                <hr>
                <h2 class="h6">Upload photos</h2>
                <form method="post" action="/donations/{{ $donation->donation_id }}/photos" enctype="multipart/form-data">
                    @csrf
                    <input
                        class="form-control mb-2"
                        type="file"
                        name="photos[]"
                        accept=".jpeg,.jpg,.png,.webp,image/jpeg,image/png,image/webp"
                        multiple
                        required
                    >
                    <button type="submit" class="btn btn-success">Upload</button>
                </form>
            @endif
        </div>
    </div>
@endsection
