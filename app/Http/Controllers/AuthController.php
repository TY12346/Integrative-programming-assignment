<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSession;
use App\Services\UserRoles\UserRoleFactoryResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private readonly UserRoleFactoryResolver $roleFactories)
    {
    }
    
    public function loginForm() { return view('auth.login'); }

    public function registerForm() { return view('auth.register'); }

    public function register(Request $request)
    {
        $data = $request->validate($this->registrationRules());
        $user = $this->roleFactories->resolve($data['role'])->register($data);

        Auth::login($user);
        $request->session()->regenerate();
        $this->recordSession($request, $user);

        return redirect('/profile')->with('message', 'Account created. Submit your verification documents to activate role features.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password_hash)
            || ! $this->roleFactories->resolve($user->role)->handler()->mayLogin($user)) {
            return back()->withErrors(['email' => 'Invalid login or unavailable account.'])->onlyInput('email');
        }

        Auth::login($user);

        $request->session()->regenerate();
        $this->recordSession($request, $user);

        return redirect('/dashboard');
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            UserSession::where('user_id', $request->user()->user_id)
                ->where('session_token', $request->session()->getId())
                ->where('session_status', 'ACTIVE')
                ->update(['logout_at' => now(), 'session_status' => 'LOGGED_OUT']);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
    
    private function registrationRules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone_no' => 'nullable|string|max:50',
            'role' => 'required|in:FOOD_DONOR,CHARITY,VOLUNTEER',
            'address' => 'nullable|string|max:1000',
        ];
    }

    private function recordSession(Request $request, User $user): void
    {
        UserSession::create([
            'user_id' => $user->user_id,
            'session_token' => $request->session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
