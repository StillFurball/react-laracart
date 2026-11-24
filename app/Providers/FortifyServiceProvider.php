<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Fortify routes registered without a rate limit, mapped to the limiter each one gets.
     *
     * @var array<string, string>
     */
    private const UNTHROTTLED_ROUTE_LIMITERS = [
        'register.store' => 'register',
        'password.email' => 'password-reset-link',
        'password.confirm.store' => 'password-confirmation',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->throttleUnthrottledRoutes();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
            'mailIsLogged' => config('mail.default') === 'log',
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        // Each account sends a verification email; cap sign-ups per address to stop scripted account spam.
        RateLimiter::for('register', fn (Request $request) => [
            Limit::perMinute(3)->by('minute:'.$request->ip()),
            Limit::perHour(20)->by('hour:'.$request->ip()),
        ]);

        // The password broker already allows one email per address per minute; this stops sweeping many addresses.
        RateLimiter::for('password-reset-link', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('password-confirmation', fn (Request $request) => Limit::perMinute(5)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));

        // Fortify's default (6 a minute, no hourly cap) lets an unverified account mail any inbox thousands of times a day.
        RateLimiter::for('verification', function (Request $request) {
            $key = (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());

            return [
                Limit::perMinute(3)->by('minute:'.$key),
                Limit::perHour(10)->by('hour:'.$key),
            ];
        });
    }

    /**
     * Fortify registers some sensitive routes without throttle middleware and offers no option for
     * them, so attach the limiters once every provider (and so every route) has booted. Cached routes
     * keep the middleware because route:cache boots the application the same way.
     */
    private function throttleUnthrottledRoutes(): void
    {
        $this->app->booted(function (): void {
            $routes = Route::getRoutes();
            $routes->refreshNameLookups();

            foreach (self::UNTHROTTLED_ROUTE_LIMITERS as $name => $limiter) {
                $routes->getByName($name)?->middleware("throttle:{$limiter}");
            }
        });
    }
}
