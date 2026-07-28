<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Policies\RegistrationPolicy;

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
        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            return 'http://localhost:3000/reset-password?token=' . $token . '&email=' . $notifiable->getEmailForPasswordReset();
        });

        Gate::policy(User::class, RegistrationPolicy::class);
    }
}
