<?php

namespace Diji\Task\Models;

use App\Traits\AutoloadRelationships;
use App\Traits\Filterable;
use App\Traits\QuerySearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskGroup extends Model
{
    use AutoloadRelationships, HasFactory, QuerySearch, Filterable;

    protected $fillable = [
        'name',
        'position',
        'project_id'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(TaskItem::class, 'task_group_id')->orderBy('position');
    }
}
