<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
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
        $this->configureDefaults();
        $this->configureErrorPages();
    }

    /**
     * Show visitors a storefront error page instead of the framework's bare one (debug mode keeps the
     * developer view). An expired session (419) goes back with a toast so the visitor can retry.
     */
    protected function configureErrorPages(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response): ?Response {
            $status = $response->statusCode();

            if ($status === 419) {
                Inertia::flash('toast', ['type' => 'warning', 'message' => 'Sesi halaman sudah berakhir. Silakan coba lagi.']);

                return back();
            }

            if (config('app.debug') || ! in_array($status, [403, 404, 429, 500], true)) {
                return null;
            }

            try {
                return $response->render('error', ['status' => $status])
                    ->withSharedData()
                    ->toResponse($response->request);
            } catch (Throwable) {
                // The page needs the database for shared props; when that is what failed, fall back.
                return null;
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        URL::forceHttps(app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
