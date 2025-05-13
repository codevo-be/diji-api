<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Upload extends Model
{
    protected $fillable = ['model_id', 'model_type', 'disk', 'path', 'filename', 'mime_type'];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return env('APP_URL') . "/api/" . Str::after($this->path, '/'); //TODO
    }
}
