<?php

namespace App\Providers;

use App\Contracts\AttachmentSecurity\FileValidatorInterface;
use App\Contracts\AttachmentSecurity\SecureFileStorageInterface;
use App\Models\University;
use App\Policies\UniversityPolicy;
use App\Services\FileValidatorService;
use App\Services\SecureFileStorageService;
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
        $this->app->singleton(
            \App\Contracts\UniversityContext::class,
            \App\Services\LaravelUniversityContext::class
        );
        $this->app->bind(
            FileValidatorInterface::class,
            FileValidatorService::class
        );
        $this->app->bind(
            SecureFileStorageInterface::class,
            function ($app) {
                return new SecureFileStorageService(
                    storage_path('app/secure-uploads')
                );
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            return 'http://localhost:3000/reset-password?token=' . $token . '&email=' . $notifiable->getEmailForPasswordReset();
        });
        Gate::policy(University::class, RegistrationPolicy::class);
        Gate::policy(User::class, RegistrationPolicy::class);
    }
}
