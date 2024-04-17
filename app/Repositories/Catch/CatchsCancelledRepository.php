<?php

namespace App\Repositories\Catch;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Catch\CatchsCancelledRespositoryInterface;
use App\Models\Catch\CatchsCancelled;
use Illuminate\Support\Facades\DB;

class CatchsCancelledRepository implements CatchsCancelledRespositoryInterface
{
    public function getAll()
    {
        return CatchsCancelled::all();
    }

    public function getByName(string $name)
    {
        return CatchsCancelled::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('catch.catchs_cancelled');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('catchs_cancelled.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['catchs_cancelled.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return CatchsCancelled::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return CatchsCancelled::create($value);
    }

    public function update(int $id, array $data)
    {
        return CatchsCancelled::whereId($id)->update($data);
    }
}
