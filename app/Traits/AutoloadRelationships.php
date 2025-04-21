<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait AutoloadRelationships
{
    protected static function bootAutoloadRelationships()
    {
        static::addGlobalScope('autoloadRelations', function (Builder $query) {
            $request = request();

            if ($request->has('include')) {
                $relations = explode(',', $request->query('include'));

                if (!empty($relations)) {
                    $validRelations = array_filter($relations, function ($relation) {
                        return method_exists(static::newModelInstance(), $relation);
                    });

                    if (!empty($validRelations)) {
                        $query->with($validRelations);
                    }
                }
            }
        });
    }
}

