<?php

namespace Diji\Task\Models;

use App\Traits\AutoloadRelationships;
use App\Traits\Filterable;
use App\Traits\QuerySearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskGroup extends Model
{
    use AutoloadRelationships, HasFactory, QuerySearch, Filterable;

    protected $fillable = [
        'name',
        'position',
        'project_id'
    ];

    protected static function booted()
    {
        parent::boot();

        static::creating(function (TaskGroup $group) {
            if(!$group->position){
                $group->position = TaskGroup::where('project_id', $group->project_id)->count() + 1;
            }
        });

        static::updating(function (TaskGroup $group) {
            $postion_before = $group->getOriginal('position');
            $postion_current = $group->position;

            if ($postion_before !== $postion_current) {
                if ($postion_before < $postion_current) {
                    TaskGroup::where('project_id', $group->project_id)
                        ->whereBetween('position', [$postion_before + 1, $postion_current])
                        ->decrement('position');
                } else {
                    TaskGroup::where('project_id', $group->project_id)
                        ->whereBetween('position', [$postion_current, $postion_before - 1])
                        ->increment('position');
                }
            }
        });
    }


    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TaskItem::class, 'task_group_id');
    }
}
