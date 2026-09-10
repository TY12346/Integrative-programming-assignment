<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSession;
use App\Services\UserRoles\UserRoleFactoryResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const MAX_FAILED_ATTEMPTS = 5;
    private const FULL_LOCKOUT_SECONDS = 900;
    private const FAILURE_WINDOW_MINUTES = 30;
    private const RATE_LIMIT_ATTEMPTS = 10;
    
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
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = Str::lower(trim($credentials['email']));
        $rateLimitKey = 'login:'.sha1($email.'|'.$request->ip());

        /*
         * Network-level throttling protects against many rapid requests,
         * including attempts using email addresses that do not exist.
         */
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()
                ->withErrors([
                    'email' => "Too many rapid login requests. Please wait {$seconds} seconds before trying again.",
                ])
                ->onlyInput('email');
        }

        $user = User::where('email', $email)->first();

        /*
         * Reject the request if the account is currently locked,
         * even when the correct password is supplied.
         */
        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            return back()
                ->withErrors([
                    'email' => $this->lockedAccountMessage($user),
                ])
                ->onlyInput('email');
        }

        /*
         * Reset the old failure counter after the lockout has expired.
         */
        if ($user && $user->locked_until && $user->locked_until->isPast()) {
            $user->forceFill([
                'failed_login_attempts' => 0,
                'last_failed_login_at' => null,
                'locked_until' => null,
            ])->save();
        }

        /*
         * Invalid email or password.
         */
        if (! $user || ! Hash::check($credentials['password'], $user->password_hash)) {
            RateLimiter::hit($rateLimitKey, 60);

            /*
             * We can only record an account lockout when the account exists.
             */
            if ($user) {
                [$attempts, $delaySeconds] = $this->recordFailedLogin($user);

                if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
                    return back()
                        ->withErrors([
                            'email' => $this->lockedAccountMessage($user),
                        ])
                        ->onlyInput('email');
                }

                return back()
                    ->withErrors([
                        'email' => "Invalid email or password. Failed attempt {$attempts} of "
                            .self::MAX_FAILED_ATTEMPTS
                            .". Please wait {$delaySeconds} seconds before trying again.",
                    ])
                    ->onlyInput('email');
            }

            /*
             * Do not reveal that the supplied email address does not exist.
             */
            return back()
                ->withErrors([
                    'email' => 'Invalid email or password.',
                ])
                ->onlyInput('email');
        }

        /*
         * The password is correct, but the account may be pending,
         * suspended, inactive or otherwise unavailable.
         */
        if (! $this->roleFactories
            ->resolve($user->role)
            ->handler()
            ->mayLogin($user)) {
            return back()
                ->withErrors([
                    'email' => 'Login denied. This account is not active or has not completed verification.',
                ])
                ->onlyInput('email');
        }

        /*
         * Successful login: clear all login-failure information.
         */
        $user->forceFill([
            'failed_login_attempts' => 0,
            'last_failed_login_at' => null,
            'locked_until' => null,
        ])->save();

        RateLimiter::clear($rateLimitKey);

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
    
    private function recordFailedLogin(User $user): array
    {
        $withinFailureWindow = $user->last_failed_login_at
            && $user->last_failed_login_at->gte(
                now()->subMinutes(self::FAILURE_WINDOW_MINUTES)
            );

        $attempts = $withinFailureWindow
            ? $user->failed_login_attempts + 1
            : 1;

        /*
         * Attempts 1–4 produce delays of 2, 4, 8 and 16 seconds.
         * Attempt 5 locks the account for 15 minutes.
         */
        $delaySeconds = $attempts >= self::MAX_FAILED_ATTEMPTS
            ? self::FULL_LOCKOUT_SECONDS
            : 2 ** $attempts;

        $user->forceFill([
            'failed_login_attempts' => $attempts,
            'last_failed_login_at' => now(),
            'locked_until' => now()->addSeconds($delaySeconds),
        ])->save();

        return [$attempts, $delaySeconds];
    }

    private function lockedAccountMessage(User $user): string
    {
        $unlockTime = $user->locked_until->format('d M Y, h:i:s A');
        $secondsRemaining = max(
            1,
            (int) ceil(now()->diffInSeconds($user->locked_until, false))
        );

        return "Account temporarily locked after repeated failed login attempts. "
            ."Locked until {$unlockTime} "
            ."({$secondsRemaining} seconds remaining).";
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
