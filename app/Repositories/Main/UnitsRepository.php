<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Main\UnitsRepositoryInterface;
use App\Models\Main\Units;
use Illuminate\Support\Facades\DB;

class UnitsRepository implements UnitsRepositoryInterface
{
    public function getAll()
    {
        return Units::all();
    }

    public function getByName(string $name)
    {
        return Units::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('main.units');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('units.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['units.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return Units::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return Units::create($value);
    }

    public function update(int $id, array $data)
    {
        return Units::whereId($id)->update($data);
    }
}
