{{--
    FoodLink - Module 3.3 Food Request Management
    Author : NG JIA QIN
    File   : resources/views/requests/index.blade.php
    Purpose: Function 2 "View Request Dashboard" and function 6 "Monitor
             Fulfillment Deadline". Shows the charity's active and historical
             requests with their tracked quantities, deadline urgency and the
             actions each request status still allows.

    Secure coding: every value is printed through Blade's escaped echo syntax,
    so text a user typed (for example the notes field) is HTML-escaped and can
    never execute as script.
--}}
@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0">Food Request Dashboard</h1>
            <small class="text-muted">Track what your organisation has asked for and how much has been committed.</small>
        </div>
        <div>
            <a class="btn btn-outline-success" href="{{ route('requests.donations') }}">Find donations</a>
            <a class="btn btn-success" href="{{ route('requests.create') }}">New request</a>
        </div>
    </div>

    {{-- Summary counters --}}
    <div class="row g-2 mb-3">
        @foreach ([
            'Total' => $summary['TOTAL'],
            'Pending' => $summary['PENDING'],
            'Partially fulfilled' => $summary['PARTIALLY_FULFILLED'],
            'Completed' => $summary['COMPLETED'],
            'Expired' => $summary['EXPIRED'],
            'Due within 24h' => $summary['URGENT'],
        ] as $label => $value)
            <div class="col-6 col-md-2">
                <div class="card h-100 text-center">
                    <div class="card-body py-2">
                        <div class="h4 mb-0">{{ $value }}</div>
                        <small class="text-muted">{{ $label }}</small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Scope tabs --}}
    <ul class="nav nav-tabs mb-3">
        @foreach (['active' => 'Active', 'history' => 'History', 'all' => 'All'] as $scope => $label)
            <li class="nav-item">
                <a class="nav-link {{ ($filters['scope'] ?? 'active') === $scope ? 'active' : '' }}"
                   href="{{ route('requests.index', ['scope' => $scope]) }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>

    {{-- Filter / search / sort --}}
    <form class="row g-2 mb-3" method="get" action="{{ route('requests.index') }}">
        <input type="hidden" name="scope" value="{{ $filters['scope'] ?? 'active' }}">
        <div class="col-md-4">
            <input class="form-control" type="search" name="keyword" maxlength="60"
                   value="{{ $filters['keyword'] }}" placeholder="Search category, unit or notes">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">All statuses</option>
                @foreach ($statuses as $code => $label)
                    <option value="{{ $code }}" @selected(($filters['status'] ?? null) === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="sort">
                @foreach ($sorts as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['sort'] ?? null) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-outline-secondary">Apply</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Food category</th>
                    <th>Progress</th>
                    <th>Fulfilment deadline</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $foodRequest)
                    @php($progress = $foodRequest->progress())
                    <tr>
                        <td>{{ $foodRequest->request_id }}</td>
                        <td>
                            {{ $foodRequest->category->category_name ?? 'Uncategorised' }}
                            @if ($foodRequest->notes)
                                <div><small class="text-muted">{{ Str::limit($foodRequest->notes, 60) }}</small></div>
                            @endif
                        </td>
                        <td style="min-width: 220px;">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: {{ $progress->percentage() }}%"></div>
                            </div>
                            <small class="text-muted">
                                {{ $foodRequest->fulfilled_quantity }} delivered ·
                                {{ $progress->reserved }} reserved of
                                {{ $foodRequest->requested_quantity }} {{ $foodRequest->unit }}
                            </small>
                        </td>
                        <td>
                            {{ $foodRequest->request_deadline?->format('d M Y H:i') }}
                            @if ($foodRequest->urgency === 'OVERDUE')
                                <span class="badge bg-danger">Overdue</span>
                            @elseif ($foodRequest->urgency === 'URGENT')
                                <span class="badge bg-warning text-dark">Due in {{ $foodRequest->hours_to_deadline }}h</span>
                            @endif
                        </td>
                        <td><span class="badge {{ $foodRequest->state()->badgeClass() }}">{{ $foodRequest->state()->label() }}</span></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('requests.show', $foodRequest->request_id) }}">View</a>
                            @if ($foodRequest->state()->canEdit())
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('requests.edit', $foodRequest->request_id) }}">Edit</a>
                            @endif
                            @if ($foodRequest->state()->canCancel())
                                <form class="d-inline" method="post" action="{{ route('requests.cancel', $foodRequest->request_id) }}"
                                      onsubmit="return confirm('Cancel this food request? Any reserved food will be returned to the donors.');">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No food requests found for this view.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $requests->links() }}
@endsection
