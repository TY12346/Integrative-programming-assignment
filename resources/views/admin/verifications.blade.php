@extends('layouts.app')

@section('content')
<h1>Manage verification requests</h1>
<form class="row g-2 mb-4">
<div class="col-md-4"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Name or email"></div>
<div class="col-md-2"><select class="form-select" name="role"><option value="">All roles</option>@foreach (['FOOD_DONOR','CHARITY','VOLUNTEER'] as $role)<option @selected(request('role') === $role)>{{ $role }}</option>@endforeach</select></div>
<div class="col-md-2"><select class="form-select" name="status"><option value="">All statuses</option>@foreach (['PENDING','APPROVED','REJECTED'] as $status)<option @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></div>
<div class="col-md-2"><select class="form-select" name="sort"><option value="created_at">Submitted</option><option value="verification_status" @selected(request('sort') === 'verification_status')>Status</option></select></div>
<div class="col-md-1"><select class="form-select" name="direction"><option value="desc">Desc</option><option value="asc" @selected(request('direction') === 'asc')>Asc</option></select></div>
<div class="col-md-1"><button class="btn btn-success w-100">Apply</button></div>
</form>
@forelse ($profiles as $profile)
<div class="card mb-3"><div class="card-body"><div class="d-flex justify-content-between"><h2 class="h5">{{ $profile->user->full_name }} <small class="text-muted">{{ $profile->user->email }}</small></h2><span class="badge text-bg-secondary">{{ $profile->verification_status }}</span></div><p>{{ $profile->user->role }} · {{ $profile->documents->count() }} document(s)</p><a class="btn btn-sm btn-outline-primary" href="/admin/verifications/{{ $profile->profile_id }}">View documents and history</a>
@if ($profile->documents->contains('document_status', 'PENDING'))<form method="post" action="/admin/verifications/{{ $profile->profile_id }}" class="row g-2 mt-2">@csrf<div class="col-md-3"><select class="form-select" name="decision"><option>APPROVED</option><option>REJECTED</option></select></div><div class="col-md-7"><input class="form-control" name="remarks" placeholder="Review remarks"></div><div class="col-md-2"><button class="btn btn-success w-100">Submit review</button></div></form>@endif
</div></div>
@empty<p class="text-muted">No verification requests found.</p>@endforelse
{{ $profiles->links() }}
@endsection