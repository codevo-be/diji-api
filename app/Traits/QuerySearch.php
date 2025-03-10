<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

trait QuerySearch
{
    /**
     * Applique les filtres de recherche à la requête.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $search
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function bootQuerySearch()
    {
        static::addGlobalScope('querySearch', function (Builder $query) {
            $request = request();

            if($request->has('search')){
                $search = trim($request->get('search'));

                /** @var \Illuminate\Database\Eloquent\Model $model */
                $model = new static();
                $columns = array_unique($model->searchable ?? []);

                if (!empty($columns)) {
                    $keywords = array_map('strtolower', explode(' ', $search));

                    $query->where(function (Builder $q) use ($keywords, $columns) {
                        foreach ($columns as $column) {
                            if (str_contains($column, '->')  ) {
                                [$jsonColumn, $jsonField] = explode('->', $column, 2);

                                $q->orWhere(function (Builder $subQuery) use ($jsonColumn, $jsonField, $keywords) {
                                    foreach ($keywords as $keyword) {
                                        $subQuery->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT($jsonColumn, '$.$jsonField'))) LIKE ?", ["%{$keyword}%"]);
                                    }
                                });
                            } else {
                                $q->orWhere(function (Builder $subQuery) use ($column, $keywords) {
                                    foreach ($keywords as $keyword) {
                                        $keyword = strtolower($keyword);
                                        $subQuery->orWhere($column, 'LIKE', "%{$keyword}%");
                                    }
                                });
                            }
                        }
                    });
                }
            }
        });
    }
}
