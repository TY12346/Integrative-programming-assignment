{{--
    FoodLink - Module 3.3 Food Request Management
    Author : NG JIA QIN
    File   : resources/views/requests/form.blade.php
    Purpose: Function 1 "Create Food Request" and function 3 "Edit Request
             Details". The same form serves both, because the validation rules
             are identical; only the target route and method differ.

    Secure coding: the CSRF directive adds an anti cross-site-request-forgery
    token to every submission, old() re-fills the form from the flashed input
    rather than from raw request data, and the unit list is a server-side
    whitelist so a tampered option value is rejected.
--}}
@extends('layouts.app')

@section('content')
    @php($isEdit = $foodRequest->exists)

    <h1 class="h3 mb-1">{{ $isEdit ? 'Edit food request #'.$foodRequest->request_id : 'Create a food request' }}</h1>
    <p class="text-muted">
        Tell donors what your organisation needs, how much of it, and by when it has to arrive.
    </p>

    <form method="post"
          action="{{ $isEdit ? route('requests.update', $foodRequest->request_id) : route('requests.store') }}"
          class="card">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label" for="category_id">Food type / category</label>
                <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                    <option value="">-- select a category --</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->category_id }}"
                            @selected((int) old('category_id', $foodRequest->category_id) === (int) $category->category_id)>
                            {{ $category->category_name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label class="form-label" for="requested_quantity">Quantity needed</label>
                <input class="form-control @error('requested_quantity') is-invalid @enderror" id="requested_quantity"
                       type="number" step="0.01" min="0.01" name="requested_quantity" required
                       value="{{ old('requested_quantity', $foodRequest->requested_quantity) }}">
                @error('requested_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label class="form-label" for="unit">Unit</label>
                <select class="form-select @error('unit') is-invalid @enderror" id="unit" name="unit" required>
                    @foreach ($units as $unit)
                        <option value="{{ $unit }}" @selected(old('unit', $foodRequest->unit) === $unit)>{{ $unit }}</option>
                    @endforeach
                </select>
                @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="request_deadline">Fulfilment deadline</label>
                <input class="form-control @error('request_deadline') is-invalid @enderror" id="request_deadline"
                       type="datetime-local" name="request_deadline" required
                       value="{{ old('request_deadline', $foodRequest->request_deadline?->format('Y-m-d\TH:i')) }}">
                <div class="form-text">Requests due within {{ config('foodlink.request.urgent_within_hours') }} hours are flagged as urgent.</div>
                @error('request_deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="notes">Special requirements <span class="text-muted">(optional)</span></label>
                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes"
                          rows="3" maxlength="500"
                          placeholder="e.g. halal only, must be chilled, deliver to the back entrance">{{ old('notes', $foodRequest->notes) }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            @if ($isEdit && ! $foodRequest->state()->canEdit())
                <div class="col-12">
                    <div class="alert alert-warning mb-0">
                        This request is already being fulfilled, so its details are locked.
                    </div>
                </div>
            @endif
        </div>

        <div class="card-footer d-flex justify-content-between">
            <a class="btn btn-outline-secondary" href="{{ route('requests.index') }}">Back</a>
            <button class="btn btn-success">{{ $isEdit ? 'Save changes' : 'Submit request' }}</button>
        </div>
    </form>
@endsection
