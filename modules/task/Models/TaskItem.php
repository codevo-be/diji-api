<?php

namespace Diji\Task\Models;

use App\Traits\AutoloadRelationships;
use App\Traits\Filterable;
use App\Traits\QuerySearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskItem extends Model
{
    use AutoloadRelationships, HasFactory, QuerySearch, Filterable;

    public const STATUS_PENDING = "pending";
    public const STATUS_IN_PROGRESS = "in_progress";
    public const STATUS_ON_HOLD = "on_hold";
    public const STATUS_REVIEW = "review";
    public const STATUS_COMPLETED = "completed";

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_ON_HOLD,
        self::STATUS_REVIEW,
        self::STATUS_COMPLETED
    ];

    protected $fillable = [
        'task_group_id',
        'task_number',
        'name',
        'description',
        'status',
        'priority',
        'position',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (TaskItem $item) {
            if(!$item->position){
                $item->position = TaskItem::where('task_group_id', $item->task_group_id)->count() + 1;
            }

            $group = TaskGroup::findOrFail($item->task_group_id);

            $last_task_number = self::whereHas('group', function ($q) use($group) {
                $q->where('project_id', $group->project_id);
            })->max('task_number');

            $item->task_number = $last_task_number ? $last_task_number + 1 : 1;
        });

        static::updating(function (TaskItem $item) {
            $postion_before = $item->getOriginal('position');
            $postion_current = $item->position;

            if ($postion_before !== $postion_current) {
                if ($postion_before < $postion_current) {
                    TaskItem::where('task_group_id', $item->task_group_id)
                        ->whereBetween('position', [$postion_before + 1, $postion_current])
                        ->decrement('position');
                } else {
                    TaskItem::where('task_group_id', $item->task_group_id)
                        ->whereBetween('position', [$postion_current, $postion_before - 1])
                        ->increment('position');
                }
            }
        });
    }

    public function group()
    {
        return $this->belongsTo(TaskGroup::class, 'task_group_id');
    }
}
