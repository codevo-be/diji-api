<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RegistrationLink extends Model
{
    use HasFactory;

    protected $table = 'registration_links';

    protected $fillable = [
        'token',
        'email',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public $timestamps = true;
}
