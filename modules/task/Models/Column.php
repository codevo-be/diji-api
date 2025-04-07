<?php

namespace Diji\Task\Models;

use Illuminate\Database\Eloquent\Model;

class Column extends Model
{
    protected $table = 'task_columns';

    protected $fillable = [
        'name',
        'order',
        'project_id'
    ];

    public function items()
    {
        return $this->hasMany(Item::class, 'task_column_id');
    }
}
