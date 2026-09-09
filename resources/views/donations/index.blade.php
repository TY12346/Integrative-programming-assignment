@extends('layouts.app')

@section('content')
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
                    <td>{{ $donation->donation_quantity }} {{ $donation->measurement_unit }}</td>
                    <td>{{ $donation->current_quantity }} {{ $donation->measurement_unit }}</td>
                    <td>{{ $donation->expiry_datetime?->format('d M Y H:i') ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $donation->statusBadgeClass() }}">{{ $donation->statusLabel() }}</span>
                    </td>
                    <td class="text-nowrap">
                        <a href="/donations/{{ $donation->donation_id }}">View</a>
                        @if ($donation->donation_status === 'AVAILABLE')
                            <a href="/donations/{{ $donation->donation_id }}/edit">Edit</a>
                            <form class="d-inline" method="post" action="/donations/{{ $donation->donation_id }}/cancel"
                                  onsubmit="return confirm('Cancel this donation?');">
                                @csrf
                                <button class="btn btn-sm btn-warning" type="submit">Cancel</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-muted">You have not created any donations yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
