<?php
namespace App\Http\Middleware; use Closure; use Illuminate\Http\Request;
class RoleMiddleware { public function handle(Request $request, Closure $next, string ...$roles) { abort_unless(auth()->check() && in_array(auth()->user()->role, $roles), 403); return $next($request); } }
