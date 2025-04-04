<?php

namespace Diji\Task\Models;

use App\Traits\QuerySearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory, QuerySearch;
    protected $table = 'task_projects';

    protected $fillable = [
        'name',
        'description',
    ];

    protected array $searchable = ['name', 'description'];
}
