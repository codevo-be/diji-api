<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Diji\Contact\Models\Contact;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function (User $user) {
            if (!$user->display_name) {
                $user->display_name = $user->setDisplayName();
            }
        });

        static::updating(function($user){
            if ($user->isDirty('firstname') || $user->isDirty('lastname')) {
                $user->display_name = $user->setDisplayName();
            }
        });
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'user_tenants');
    }

    private function setDisplayName()
    {
        return trim("{$this->firstname} {$this->lastname}");
    }
}
