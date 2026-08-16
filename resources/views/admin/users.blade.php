@extends('layouts.app')

@section('content')
<h1>Manage users</h1>
<form class="row g-2 mb-4">
    <div class="col-md-3"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Name or email"></div>
    <div class="col-md-2"><select class="form-select" name="role"><option value="">All roles</option>@foreach (['FOOD_DONOR','CHARITY','VOLUNTEER','ADMIN'] as $role)<option @selected(request('role') === $role)>{{ $role }}</option>@endforeach</select></div>
    <div class="col-md-2"><select class="form-select" name="status"><option value="">All account statuses</option>@foreach (['PENDING','ACTIVE','INACTIVE','SUSPENDED','DELETED'] as $status)<option @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></div>
    <div class="col-md-2"><select class="form-select" name="verification_status"><option value="">All verification</option>@foreach (['PENDING','APPROVED','REJECTED'] as $status)<option @selected(request('verification_status') === $status)>{{ $status }}</option>@endforeach</select></div>
    <div class="col-md-2"><select class="form-select" name="sort"><option value="created_at">Newest</option><option value="full_name" @selected(request('sort') === 'full_name')>Name</option><option value="email" @selected(request('sort') === 'email')>Email</option><option value="role" @selected(request('sort') === 'role')>Role</option><option value="account_status" @selected(request('sort') === 'account_status')>Status</option></select></div>
    <div class="col-md-1"><button class="btn btn-success w-100">Apply</button></div>
</form>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Verification</th><th>Account status</th></tr></thead><tbody>
@forelse ($users as $user)
<tr><td>{{ $user->full_name }}</td><td>{{ $user->email }}</td><td>{{ $user->role }}</td><td>{{ $user->partnerProfile?->verification_status ?? 'N/A' }}</td><td><form method="post" action="/admin/users/{{ $user->user_id }}" class="d-flex gap-2">@csrf @method('PATCH')<select class="form-select form-select-sm" name="account_status">@foreach (['PENDING','ACTIVE','INACTIVE','SUSPENDED','DELETED'] as $status)<option @selected($user->account_status === $status)>{{ $status }}</option>@endforeach</select><button class="btn btn-sm btn-primary">Update</button></form></td></tr>
@empty<tr><td colspan="5" class="text-muted">No users found.</td></tr>@endforelse
</tbody></table></div>
{{ $users->links() }}

<hr class="my-5">
<h2 class="h4">Create administrator</h2>
<p class="text-muted">Administrator accounts can only be created here by an authenticated administrator.</p>
<form method="post" action="/admin/users" class="row g-2">@csrf
<div class="col-md-3"><input class="form-control" name="full_name" placeholder="Full name" required></div><div class="col-md-3"><input class="form-control" type="email" name="email" placeholder="Email" required></div><div class="col-md-3"><input class="form-control" type="password" name="password" placeholder="Password" required></div><div class="col-md-3"><input class="form-control" type="password" name="password_confirmation" placeholder="Confirm password" required></div><div class="col-12"><button class="btn btn-dark">Create administrator</button></div>
</form>
@endsection