<?php
namespace App\Traits;

use Illuminate\Http\Request;

trait Filterable
{
    public function scopeFilter($query, array $allowed = [])
    {
        $request = request();

        foreach ($allowed as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        return $query;
    }
}
