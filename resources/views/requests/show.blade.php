{{--
    FoodLink - Module 3.3 Food Request Management
    Author : NG JIA QIN
    File   : resources/views/requests/show.blade.php
    Purpose: Function 5 "Track Reserved Quantity", function 6 "Monitor
             Fulfillment Deadline" and function 7 "Check Request Status".
             Shows the live quantity breakdown, the reservations donors have
             committed, the delivery state reported by module 3.4, and the full
             status history of the request.
--}}
@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h3 mb-1">
                Request #{{ $foodRequest->request_id }} ·
                {{ $foodRequest->category->category_name ?? 'Uncategorised' }}
            </h1>
            <span class="badge {{ $foodRequest->state()->badgeClass() }}">{{ $foodRequest->state()->label() }}</span>
            @if ($foodRequest->urgency === 'OVERDUE')
                <span class="badge bg-danger">Deadline passed</span>
            @elseif ($foodRequest->urgency === 'URGENT')
                <span class="badge bg-warning text-dark">Due in {{ $foodRequest->hours_to_deadline }} hour(s)</span>
            @endif
        </div>
        <div class="text-nowrap">
            <a class="btn btn-outline-secondary" href="{{ route('requests.index') }}">Back</a>
            @if ($foodRequest->state()->canReserve())
                <a class="btn btn-success"
                   href="{{ route('requests.donations', ['request_id' => $foodRequest->request_id, 'category_id' => $foodRequest->category_id]) }}">
                    Reserve a donation
                </a>
            @endif
            @if ($foodRequest->state()->canEdit())
                <a class="btn btn-outline-primary" href="{{ route('requests.edit', $foodRequest->request_id) }}">Edit</a>
            @endif
        </div>
    </div>

    <div class="row g-3">
        {{-- Quantity tracking --}}
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Fulfilment progress</div>
                <div class="card-body">
                    <div class="progress mb-3" style="height: 12px;">
                        <div class="progress-bar bg-success" style="width: {{ $progress->percentage() }}%">
                            {{ $progress->percentage() }}%
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-6 col-md-3">
                            <div class="h5 mb-0">{{ $foodRequest->requested_quantity }}</div>
                            <small class="text-muted">Requested</small>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="h5 mb-0">{{ round($progress->reserved, 2) }}</div>
                            <small class="text-muted">Reserved by donors</small>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="h5 mb-0">{{ $foodRequest->fulfilled_quantity }}</div>
                            <small class="text-muted">Delivered</small>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="h5 mb-0">{{ round($progress->outstanding(), 2) }}</div>
                            <small class="text-muted">Still needed</small>
                        </div>
                    </div>
                    <hr>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Unit</dt>
                        <dd class="col-sm-8">{{ $foodRequest->unit }}</dd>
                        <dt class="col-sm-4">Fulfilment deadline</dt>
                        <dd class="col-sm-8">{{ $foodRequest->request_deadline?->format('d M Y H:i') }}</dd>
                        <dt class="col-sm-4">Submitted on</dt>
                        <dd class="col-sm-8">{{ $foodRequest->created_at?->format('d M Y H:i') }}</dd>
                        @if ($foodRequest->notes)
                            <dt class="col-sm-4">Special requirements</dt>
                            <dd class="col-sm-8">{{ $foodRequest->notes }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Reservations - the reserved quantity committed by donors --}}
            <div class="card">
                <div class="card-header">Reserved donations</div>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Donation</th>
                                <th>Quantity</th>
                                <th>Pickup by</th>
                                <th>Reservation</th>
                                <th>Delivery</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($foodRequest->reservations as $reservation)
                                <tr>
                                    <td>{{ $reservation->reservation_id }}</td>
                                    <td>
                                        {{ $reservation->donation->food_name ?? 'Donation removed' }}
                                        <div><small class="text-muted">{{ $reservation->donation->donor->user->full_name ?? '' }}</small></div>
                                    </td>
                                    <td>{{ $reservation->reserved_quantity }} {{ $reservation->donation->measurement_unit ?? $foodRequest->unit }}</td>
                                    <td>{{ $reservation->pickup_deadline?->format('d M Y H:i') }}</td>
                                    <td><span class="badge {{ $reservation->statusBadgeClass() }}">{{ $reservation->reservation_status }}</span></td>
                                    <td>
                                        @if ($reservation->deliveryTask)
                                            <span class="badge bg-info text-dark">{{ $reservation->deliveryTask->delivery_status }}</span>
                                        @else
                                            <small class="text-muted">Not assigned</small>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($reservation->isCancellable() && $foodRequest->state()->canReserve())
                                            <form method="post"
                                                  action="{{ route('requests.reservations.release', [$foodRequest->request_id, $reservation->reservation_id]) }}"
                                                  onsubmit="return confirm('Withdraw this reservation and return the food to the donor?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Withdraw</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No donation has been reserved for this request yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{--
            Request timeline. Derived from the request row and its reservations
            rather than from a separate history entity, because the analysis
            class diagram defines no history class for a food request.
        --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Request timeline</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <strong>Request submitted</strong>
                            <small class="text-muted">{{ $foodRequest->created_at?->format('d M H:i') }}</small>
                        </div>
                        <small class="text-muted">
                            {{ $foodRequest->requested_quantity }} {{ $foodRequest->unit }} requested
                        </small>
                    </li>

                    @foreach ($foodRequest->reservations->sortBy('created_at') as $reservation)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <strong>
                                    @if ($reservation->reservation_status === \App\Models\Reservation::COMPLETED)
                                        Delivered
                                    @elseif ($reservation->reservation_status === \App\Models\Reservation::CANCELLED)
                                        Reservation withdrawn
                                    @else
                                        Reserved by donor
                                    @endif
                                </strong>
                                <small class="text-muted">{{ $reservation->created_at?->format('d M H:i') }}</small>
                            </div>
                            <small class="text-muted">
                                {{ $reservation->reserved_quantity }}
                                {{ $reservation->donation->measurement_unit ?? $foodRequest->unit }}
                                · {{ $reservation->donation->food_name ?? 'donation removed' }}
                            </small>
                        </li>
                    @endforeach

                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <strong>Now: {{ $foodRequest->state()->label() }}</strong>
                            <small class="text-muted">{{ $foodRequest->request_deadline?->format('d M H:i') }}</small>
                        </div>
                        <small class="text-muted">
                            @if ($foodRequest->state()->isFinal())
                                No further change is possible.
                            @elseif ($foodRequest->isPastDeadline())
                                The fulfilment deadline has passed.
                            @else
                                {{ round($progress->outstanding(), 2) }} {{ $foodRequest->unit }} still needed
                                before the deadline.
                            @endif
                        </small>
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection
