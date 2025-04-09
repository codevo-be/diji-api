<?php

namespace Diji\Task\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'task_items';

    protected $fillable = [
        'task_column_id',
        'name',
        'description',
        'status',
        'priority',
        'order',
        'done'
    ];

    public function column()
    {
        return $this->belongsTo(Column::class, 'task_column_id');
    }
}
