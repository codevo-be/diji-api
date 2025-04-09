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
        'done',
        'task_number',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Item $item) {
            // Assure que la colonne est chargée
            $column = Column::find($item->task_column_id);

            if ($column) {
                $projectId = $column->project_id;

                $lastNumber = self::whereHas('column', function ($q) use ($projectId) {
                    $q->where('project_id', $projectId);
                })->max('task_number');

                $item->task_number = $lastNumber ? $lastNumber + 1 : 1;
            }
        });
    }

    public function column()
    {
        return $this->belongsTo(Column::class, 'task_column_id');
    }
}
