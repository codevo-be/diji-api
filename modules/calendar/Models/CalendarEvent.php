<?php

namespace Diji\Calendar\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start',
        'end',
        'all_day',
    ];

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'calendar_event_user', 'calendar_event_id', 'user_id');
    }
}
