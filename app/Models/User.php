<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token', 'updated_at'])]
class User extends Authenticatable
{
    
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles, HasApiTokens;

    /**
     * Spatie permission/role lookups must match RoleAndPermissionSeeder (guard: api).
     */
    protected string $guard_name = 'api';

    // append the Role label accessor
    protected $appends = ['role_label'];

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




    // return the label of the rule for show
    protected function roleLabel(): Attribute
    {
        return Attribute::get(
            fn(): ?string => match ($this->role) {
                'super_admin' => 'super admin',
                'uni_admin' => 'university admin',
                'alumni' => 'alumni',
                'student' => 'student',
                default => $this->role,
            }
        );
    }

    // Relatioships
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
