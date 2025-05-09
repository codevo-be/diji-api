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

    public function group()
    {
        return $this->belongsTo(TaskGroup::class, 'task_group_id');
    }
}
