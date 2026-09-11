@extends('layouts.app')

@section('content')
    @php
        $formatDonationQuantity = function ($quantity, $unit): string {
            $countUnits = ['packs', 'boxes', 'trays', 'pieces', 'meals'];
            $value = (float) $quantity;

            if (in_array((string) $unit, $countUnits, true) && abs($value - (int) $value) < 0.00001) {
                return (string) (int) $value;
            }

            return number_format($value, 2, '.', '');
        };
    @endphp

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h3 mb-1">{{ $donation->food_name }}</h1>
            <span class="badge {{ $donation->statusBadgeClass() }}">{{ $donation->statusLabel() }}</span>
        </div>
        <a class="btn btn-outline-secondary"
            href="{{ auth()->user()->role === \App\Models\User::ROLE_FOOD_DONOR
                ? url('/donations')
                : url('/donations/available') }}">
            Back
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-header">Quantity tracking</div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="h4 mb-0">{{ $formatDonationQuantity($donation->donation_quantity, $donation->measurement_unit) }} {{ $donation->measurement_unit }}</div>
                    <small class="text-muted">Original quantity donated</small>
                </div>
                <div class="col-md-4">
                    <div class="h4 mb-0">{{ $formatDonationQuantity($donation->current_quantity, $donation->measurement_unit) }} {{ $donation->measurement_unit }}</div>
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
                <dd class="col-sm-8">{{ $formatDonationQuantity($donation->donation_quantity, $donation->measurement_unit) }} {{ $donation->measurement_unit }}</dd>

                <dt class="col-sm-4">Remaining available quantity</dt>
                <dd class="col-sm-8">{{ $formatDonationQuantity($donation->current_quantity, $donation->measurement_unit) }} {{ $donation->measurement_unit }}</dd>

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
                <p class="text-muted mb-0">No photos available</p>
            @else
                <div class="row g-3">
                    @foreach ($donation->photos as $photo)
                        <div class="col-6 col-md-4 col-lg-3">
                            <img
                                class="img-fluid rounded border"
                                src="{{ url('/donations/photos/'.$photo->photo_id.'/file') }}"
                                alt="Donation photo {{ $photo->photo_id }}"
                            >
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
