@extends('layouts.app')

@section('content')
<h1>Register</h1>
<p class="text-muted">Create a donor, charity, or volunteer account. An administrator must approve your verification request before role features become available.</p>
<form method="post" class="row g-3">
    @csrf
    <div class="col-md-6"><label class="form-label">Full name</label><input class="form-control" name="full_name" value="{{ old('full_name') }}" required></div>
    <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email') }}" required></div>
    <div class="col-md-6"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required></div>
    <div class="col-md-6"><label class="form-label">Confirm password</label><input class="form-control" type="password" name="password_confirmation" required></div>
    <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone_no" value="{{ old('phone_no') }}"></div>
    <div class="col-md-6"><label class="form-label">Role</label><select class="form-select" name="role" required><option value="FOOD_DONOR">Food Donor</option><option value="CHARITY">Charity</option><option value="VOLUNTEER">Volunteer</option></select></div>
    <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address">{{ old('address') }}</textarea></div>
    <div class="col-12"><button class="btn btn-success">Create account</button></div>
</form>
@endsection