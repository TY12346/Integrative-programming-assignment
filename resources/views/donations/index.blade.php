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

    <h1 class="h3 mb-3">My Donations</h1>
    <a class="btn btn-success mb-3" href="/donations/create">Create donation</a>

    <table class="table">
        <thead>
            <tr>
                <th>Food</th>
                <th>Original qty</th>
                <th>Remaining qty</th>
                <th>Expiry</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($donations as $donation)
                <tr>
                    <td>{{ $donation->food_name }}</td>
                    <td>{{ $formatDonationQuantity($donation->donation_quantity, $donation->measurement_unit) }} {{ $donation->measurement_unit }}</td>
                    <td>{{ $formatDonationQuantity($donation->current_quantity, $donation->measurement_unit) }} {{ $donation->measurement_unit }}</td>
                    <td>{{ $donation->expiry_datetime?->format('d M Y H:i') ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $donation->statusBadgeClass() }}">{{ $donation->statusLabel() }}</span>
                    </td>
                    <td class="text-nowrap">
                        <div class="d-inline-flex align-items-center gap-2">
                            <a class="btn btn-sm btn-outline-success" href="{{ route('donations.show', $donation) }}">View</a>
                            @if ($donation->donation_status === 'AVAILABLE')
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('donations.edit', $donation) }}">Edit</a>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#cancelDonationModal{{ $donation->donation_id }}"
                                >
                                    Cancel
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-muted">You have not created any donations yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @foreach ($donations as $donation)
        @if ($donation->donation_status === 'AVAILABLE')
            <div
                class="modal fade"
                id="cancelDonationModal{{ $donation->donation_id }}"
                tabindex="-1"
                aria-labelledby="cancelDonationModalLabel{{ $donation->donation_id }}"
                aria-hidden="true"
            >
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="cancelDonationModalLabel{{ $donation->donation_id }}">
                                Cancel donation?
                            </h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to cancel this donation? This action cannot be undone.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Donation</button>
                            <form method="post" action="/donations/{{ $donation->donation_id }}/cancel" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning">Cancel Donation</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@endsection
