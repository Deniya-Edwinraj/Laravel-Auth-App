<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; 
use Illuminate\Support\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens; 

    // Add role to fillable
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',  // Added role
        'first_login',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'first_login' => 'datetime',
            'last_login' => 'datetime',
        ];
    }

    // Accessor for full name
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Check if user is admin
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // Check if user is normal user
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    // Update login timestamps
    public function updateLoginTimestamps(): void
    {
        $now = Carbon::now();
        
        if (!$this->first_login) {
            $this->first_login = $now;
        }
        
        $this->last_login = $now;
        $this->save();
    }
}