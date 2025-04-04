<?php

namespace Diji\Task\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Column extends Model
{
    protected $table = 'task_columns';

    protected $fillable = [
        'name',
        'order',
        'columnable_id',
        'columnable_type',
    ];
}
