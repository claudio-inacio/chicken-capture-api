<?php

namespace App\Repositories\Catch;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Catch\CatchDailyRespositoryInterface;
use App\Models\Catch\CatchDaily;
use Illuminate\Support\Facades\DB;

class CatchDailyRepository implements CatchDailyRespositoryInterface
{
    public function getAll()
    {
        return CatchDaily::all();
    }

    public function getByName(string $name)
    {
        return CatchDaily::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('catch.catch_daily');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('catch_daily.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['catch_daily.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return CatchDaily::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return CatchDaily::create($value);
    }

    public function update(int $id, array $data)
    {
        return CatchDaily::whereId($id)->update($data);
    }
}
