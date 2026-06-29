<?php

namespace App\Http\Controllers;

use App\Models\PartnerProfile;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:4',
            'phone_no' => 'nullable|string|max:50',
            'role' => 'required|in:FOOD_DONOR,CHARITY,VOLUNTEER,ADMIN',
            'address' => 'nullable|string',
        ]);

        $user = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'phone_no' => $data['phone_no'] ?? null,
            'role' => $data['role'],
            'account_status' => 'ACTIVE',
        ]);

        if ($user->role !== 'ADMIN') {
            PartnerProfile::create([
                'user_id' => $user->user_id,
                'address' => $data['address'] ?? null,
            ]);
        }

        Auth::login($user);

        return redirect('/dashboard');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password_hash) || $user->account_status !== 'ACTIVE') {
            return back()->withErrors(['email' => 'Invalid login or inactive account.']);
        }

        Auth::login($user);

        UserSession::create([
            'user_id' => $user->user_id,
            'session_token' => session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect('/dashboard');
    }

    public function logout()
    {
        if (auth()->check()) {
            UserSession::where('user_id', auth()->id())
                ->where('session_status', 'ACTIVE')
                ->update([
                    'logout_at' => now(),
                    'session_status' => 'LOGGED_OUT',
                ]);
        }

        Auth::logout();
        request()->session()->invalidate();

        return redirect('/login');
    }
}
