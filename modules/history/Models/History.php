<?php

namespace Diji\History\Models;

use App\Traits\AutoloadRelationships;
use App\Traits\Filterable;
use App\Traits\QuerySearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use AutoloadRelationships, HasFactory, QuerySearch, Filterable;

    protected $fillable = [
        'model_id',
        'model_type',
        'message',
        'type'
    ];

    protected static function booted()
    {
        parent::boot();
    }
}
