<?php
namespace App\Factory;
use \Illuminate\Database\Query\Builder;

/**
 * {
 *      "and": [
 * "LIKE": {
 * field: 'nome',
 * values: ['Joao da silva', 'maria', 'jose' ]
 * },
 * ],
 * ">=": {
 * field: 'idade',
 * value: 55
 * }
 * }
 **/
class WhereFactory {
    private array $commandsOneParameter = [ '=', '>=', '<=', 'iLIKE', 'LIKE'];
    private array $commandsDontHaveValue = [ 'whereNotNull'];
    private array $commandsTwoParameter = [ 'whereBetween', 'whereIn', 'whereNotIn', 'where'];

    private function addQuerysByConfig(Builder $query, $command2, array $config = []) : Builder{
        if(in_array($command2, $this->commandsOneParameter))
            $query->where($config['field'], $command2, $config['value']);
        else if(in_array($command2, $this->commandsTwoParameter))
            $query->{$command2}($config['field'], $config['value']);
        else if(in_array($command2, $this->commandsDontHaveValue))
            $query->{$command2}($config['field']);
        else if($command2 === 'whereDay')
            $query->whereDay($config['field'], '=', $config['value']);
        else if($command2 === 'whereCurrentDate')
            $query->whereDate($config['field'], '=', date("Y-m-d"));
        else if($command2 === 'whereDateBetween') {
            $start =  $config['value']['start'];
            $end = $config['value']['end'];
            $query->whereBetween("{$config['field']}", [$start, $end]);
        }

//        $query->whereIn($command2, [1233]);
        return $query;
    }

    public function byArray(Builder $query, array $arrayWhere = []) : Builder{
        foreach($arrayWhere  as $currentConfig){
            $command = $currentConfig['command'];
            if($command === 'and' OR $command === 'AND') {
                $value = $currentConfig['value'];
                $query->where(function($query) use ($value) {
                    foreach ($value as  $config){
                        $command2 = $config['command'];
                        $query = $this->addQuerysByConfig($query, $command2, $config);
                    }
                });

                continue;
            }
            $query = $this->addQuerysByConfig($query, $command, $currentConfig);
        }

        return $query;
    }
}
