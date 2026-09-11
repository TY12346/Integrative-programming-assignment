@extends('layouts.app')

@section('content')
<div class="card border-danger">
    <div class="card-header bg-danger text-white">
        <h1 class="h4 mb-0">403 — Access Denied</h1>
    </div>

    <div class="card-body">
        <div class="alert alert-danger">
            {{ $exception->getMessage() }}
        </div>

        @auth
            <p>
                <strong>Logged-in user:</strong>
                {{ auth()->user()->email }}
            </p>

            <p>
                <strong>Current role:</strong>
                {{ auth()->user()->role }}
            </p>
        @endauth

        <a href="/dashboard" class="btn btn-primary">
            Return to Dashboard
        </a>
    </div>
</div>
@endsection