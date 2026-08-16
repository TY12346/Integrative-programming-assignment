@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Profile</h1>
    <div><span class="badge text-bg-secondary">{{ $user->role }}</span> <span class="badge text-bg-{{ $user->account_status === 'ACTIVE' ? 'success' : 'warning' }}">{{ $user->account_status }}</span></div>
</div>
<form method="post" class="card card-body mb-4">
    @csrf
    <label class="form-label">Full name</label><input class="form-control mb-2" name="full_name" value="{{ old('full_name', $user->full_name) }}" required>
    <label class="form-label">Phone number</label><input class="form-control mb-2" name="phone_no" value="{{ old('phone_no', $user->phone_no) }}">
    @if ($user->role !== 'ADMIN')
        <label class="form-label">Address</label><textarea class="form-control mb-3" name="address">{{ old('address', $user->partnerProfile?->address) }}</textarea>
    @endif
    <div><button class="btn btn-success">Save profile</button></div>
</form>

@if ($user->role !== 'ADMIN')
<div class="card mb-4">
    <div class="card-header"><strong>Submit verification request</strong></div>
    <div class="card-body">
        <p>Current verification status: <span class="badge text-bg-secondary">{{ $user->partnerProfile->verification_status }}</span></p>
        <form method="post" action="/profile/document" enctype="multipart/form-data" class="row g-2">
            @csrf
            <div class="col-md-5"><select class="form-select" name="document_type" required><option value="">Choose document type</option>@foreach ($documentTypes as $type)<option value="{{ $type }}">{{ str_replace('_', ' ', $type) }}</option>@endforeach</select></div>
            <div class="col-md-5"><input class="form-control" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required></div>
            <div class="col-md-2"><button class="btn btn-outline-success w-100">Upload</button></div>
        </form>
    </div>
</div>

<h2 class="h4">Verification request history</h2>
<div class="table-responsive mb-4"><table class="table table-striped"><thead><tr><th>Submitted</th><th>Document</th><th>Status</th></tr></thead><tbody>
@forelse ($user->partnerProfile->documents->sortByDesc('submitted_at') as $document)
<tr><td>{{ $document->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td><td>{{ str_replace('_', ' ', $document->document_type) }}</td><td><span class="badge text-bg-secondary">{{ $document->document_status }}</span></td></tr>
@empty<tr><td colspan="3" class="text-muted">No verification documents submitted.</td></tr>@endforelse
</tbody></table></div>
<div class="table-responsive mb-4"><table class="table table-sm"><thead><tr><th>Reviewed</th><th>Decision</th><th>Reviewer</th><th>Remarks</th></tr></thead><tbody>
@forelse ($user->partnerProfile->reviews->sortByDesc('reviewed_at') as $review)
<tr><td>{{ $review->reviewed_at?->format('Y-m-d H:i') }}</td><td>{{ $review->decision }}</td><td>{{ $review->reviewer->full_name }}</td><td>{{ $review->remarks ?: '—' }}</td></tr>
@empty<tr><td colspan="4" class="text-muted">No reviews yet.</td></tr>@endforelse
</tbody></table></div>

<div class="card border-danger"><div class="card-body"><h2 class="h5 text-danger">Delete account</h2><p class="text-muted">Deletion is unavailable while you have active role responsibilities.</p><form method="post" action="/profile" onsubmit="return confirm('Delete your account? This cannot be undone.')">@csrf @method('DELETE')<button class="btn btn-danger">Delete my account</button></form></div></div>
@endif
@endsection