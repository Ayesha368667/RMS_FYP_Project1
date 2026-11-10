<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Filament\Models\Contracts\HasDatabaseNotifications; // <-- Yeh lazmi hai
use Filament\Models\Concerns\InteractsWithDatabaseNotifications;


use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
         return $this->is_active && $this->role === $panel->getId();
    }

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'previous_password',
        'must_change_password', 'role', 'preffered_language', 'is_active',
    ];

    protected $hidden = ['password', 'previous_password', 'remember_token'];

    protected function casts(): array
    {
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
    public function isOldPassword($newPassword)
    {
        // naya password abhi wale jaisa ho
        if (Hash::check($newPassword, $this->password)) {
            return true;
        }

        // naya password pichle wale jaisa ho
        if ($this->previous_password && Hash::check($newPassword, $this->previous_password)) {
            return true;
        }

        return false;
    }
}

