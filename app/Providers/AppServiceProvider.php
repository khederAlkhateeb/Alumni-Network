<?php

namespace App\Providers;

use App\Models\University;
use App\Policies\UniversityPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Policies\RegistrationPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \App\Contracts\UniversityContext::class,
            \App\Services\LaravelUniversityContext::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, RegistrationPolicy::class);
    }
}
