<?php
namespace App\Factory;
use \Illuminate\Database\Query\Builder;

class SelectFactory {
    public static function byArray(Builder $query, array $select = []) : Builder{
        foreach($select  as $command => $value){
            if($command === 'orderBy') {
                $query->orderBy($value[0], $value[1]);
            }
            else if($command === 'groupBy') {
                $query->groupBy($value);
            }
        }

        if(!empty($select['offset']))
            $query->offset($select['offset']);
        if(!empty($select['limit']))
            $query->limit($select['limit']);

        return $query;
    }
}
