<?php

namespace Diji\Project\Models;

use App\Traits\AutoloadRelationships;
use App\Traits\Filterable;
use App\Traits\QuerySearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use AutoloadRelationships, HasFactory, QuerySearch, Filterable;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
    ];

    protected array $searchable = ['name'];
}
