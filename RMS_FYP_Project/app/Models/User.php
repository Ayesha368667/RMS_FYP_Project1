<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
        //tells which terms to save in database
    protected $fillable = [
    'name', 'email', 'phone', 'password', 'previous_password',
    'must_change_password', 'role', 'preffered_language', 'is_active',
    ];
        //hides important data if send in json format
    protected $hidden = ['password', 'previous_password', 'remember_token'];

    protected function casts(): array
    {
        //saves data_conversion which will be automatically done.
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function hasRole(string $role): bool
{
    return $this->role === $role;
}

    public function patient() {
         return $this->hasOne(Patient::class); 
    }
    public function doctor() { 
         return $this->hasOne(Doctor::class); 
    }
    public function activityLogs() { 
        return $this->hasMany(ActivityLog::class); 
    }
}
