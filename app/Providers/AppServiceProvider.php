<?php

namespace App\Providers;

use App\Contracts\AttachmentSecurity\FileValidatorInterface;
use App\Contracts\AttachmentSecurity\SecureFileStorageInterface;
use App\Events\PostCreated;
use App\Listeners\InvalidateFeedCacheForConnections;
use App\Models\Comment;
use App\Models\Post;
use App\Models\University;
use App\Policies\UniversityPolicy;
use App\Services\FileValidatorService;
use App\Services\SecureFileStorageService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Policies\RegistrationPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        PostCreated::class => [
            InvalidateFeedCacheForConnections::class,
        ],
    ];
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
        // Registers short aliases ('post', 'comment') for polymorphic
        // relations, so that `reactable_type` is stored as a clean short
        // string in the database instead of the full class name
        // (App\Models\Post). Eloquent then resolves these aliases back
        // to the real model class automatically on every morphTo() call.
        Relation::morphMap([
            'post'    => Post::class,
            'comment' => Comment::class,
        ]);

        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            return 'http://localhost:3000/reset-password?token=' . $token . '&email=' . $notifiable->getEmailForPasswordReset();
        });
        Gate::policy(University::class, UniversityPolicy::class);
        Gate::policy(User::class, RegistrationPolicy::class);
    }
}
