<?php

namespace LibLaravel;
use Illuminate\Support\Facades\DB;

class FilterService
{
    protected static $user;

    public static function getFilters($columns, $request, $model){
        $filters = collect($columns)->map(function ($column) use ($request) {
            $key = $column['key'];
            return [
                'name' => $key,
                'type' => $column['search']['type'] ?? null,
                'value' => $request->input($key), 
                'relation' => isset($column['relation']) ? $column['relation'] : null
            ];
        })->values()->toArray();

        $model = $model::query();
        foreach ($filters as $filter) {
            $name = $filter['name'];
            $value = $filter['value'];
            $type = $filter['type'];
            $relation = $filter['relation'];
    
            if ($value === null || $value === '') {
                continue;
            }
            if($relation){
                $model->whereHas($relation, function ($query) use($name, $value,  $type){
                    self::conditionalWhere($query, $name, $value,  $type);
                });
            }else{
                self::conditionalWhere($model, $name, $value,  $type);
            }
           
        }
        return $model;
    }

    public static function conditionalWhere($query, $name, $value,  $type){
        if ($type === 'input') {
            $query->where(DB::raw("UPPER($name)"), 'like', '%' . mb_strtoupper($value) . '%');
        } elseif ($type === 'select') {
            $query->where($name, $value);
        } 
    }
}