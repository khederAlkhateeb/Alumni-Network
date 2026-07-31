<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password','is_active'])]
#[Hidden(['password', 'remember_token', 'updated_at'])]
class User extends Authenticatable
{
    protected $guard_name = 'api';
    
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles, HasApiTokens;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function universityAdmin(): HasOne
    {
        return $this->hasOne(UniversityAdmin::class);
    }

    public function alumniProfile(): HasOne
    {
        return $this->hasOne(AlumniProfile::class, 'user_id');
    }

}
