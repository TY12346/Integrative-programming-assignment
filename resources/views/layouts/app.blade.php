<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FoodLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4">
    <div class="container">
        <a class="navbar-brand" href="/dashboard">FoodLink</a>
        <div class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
            @auth
                <a class="nav-link" href="/profile">Profile</a>
                @php($hasRoleAccess = \App\Services\UserRoles\UserRoleHandler::for(auth()->user()->role)->mayAccessRoleFeatures(auth()->user()))
                @if ($hasRoleAccess)
                    <a class="nav-link" href="/donations/available">Available Donations</a>
                    @if (auth()->user()->role === 'ADMIN')
                        <a class="nav-link" href="/admin/users">Users</a>
                        <a class="nav-link" href="/admin/verifications">Verifications</a>
                    @endif
                    @if (auth()->user()->role === 'FOOD_DONOR')
                        <a class="nav-link" href="/donations">My Donations</a>
                    @endif
                    @if (auth()->user()->role === 'CHARITY')
                        <a class="nav-link" href="/requests">My Requests</a>
                        <a class="nav-link" href="/requests/donations">Find Donations</a>
                    @endif
                    @if (in_array(auth()->user()->role, ['VOLUNTEER', 'ADMIN'], true))
                        <a class="nav-link" href="/deliveries">Deliveries</a>
                    @endif
                @else
                    <span class="navbar-text badge text-bg-warning">Verification pending</span>
                @endif
                <form method="post" action="/logout" class="ms-lg-2">
                    @csrf
                    <button class="btn btn-sm btn-light">Logout</button>
                </form>
            @endauth
        </div>
    </div>
</nav>
<main class="container pb-5">
    @if (session('message'))<div class="alert alert-info">{{ session('message') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @yield('content')
</main>
</body>
</html>