<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $fillable = ['model_id', 'model_type', 'path', 'filename', 'mime_type'];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return env('APP_URL') . "/" . $this->path;
    }
}
