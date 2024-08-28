<?php

namespace App\Repositories\Region;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Region\CityRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CityRepository implements CityRepositoryInterface
{
    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('region.city');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('city.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['city.*']);

        $result = $query->get()->toArray();

        return [
            'data' => $result,
            'total' => $total,
        ];
    }
}
