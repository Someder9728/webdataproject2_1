<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {

        Fortify::username('u_username');

        Fortify::authenticateUsing(function (Request $request) {
            $username = Str::lower(
                trim((string) $request->input('u_username'))
            );

            $user = User::where('u_username', $username)->first();

            if (! $user) {
                return null;
            }

            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    'u_username' => 'บัญชีถูกระงับ กรุณาติดต่อผู้ดูแล',
                ]);
            }

            if (
                Hash::check(
                    (string) $request->input('password'),
                    $user->u_password
                )
            ) {
                return $user;
            }

            return null;
        });

        /* Login View */
        Fortify::loginView(function () {
            return view('pages::auth.login');
        });

        /* Other Fortify Views */
        Fortify::verifyEmailView(function () {
            return view('pages::auth.verify-email');
        });

        Fortify::twoFactorChallengeView(function () {
            return view('pages::auth.two-factor-challenge');
        });

        Fortify::confirmPasswordView(function () {
            return view('pages::auth.confirm-password');
        });

        Fortify::registerView(function () {
            return view('pages::auth.register');
        });

        Fortify::resetPasswordView(function () {
            return view('pages::auth.reset-password');
        });

        Fortify::requestPasswordResetLinkView(function () {
            return view('pages::auth.forgot-password');
        });

        /* Rate Limiting */
        RateLimiter::for('login', function (Request $request) {
            $username = Str::lower(
                (string) $request->input('u_username')
            );

            return Limit::perMinute(5)
                ->by($username . '|' . $request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip());
        });
    }
}