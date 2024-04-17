<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Main\CollectorsRepositoryInterface;
use App\Models\Main\Collectors;
use Illuminate\Support\Facades\DB;

class CollectorsRepository implements CollectorsRepositoryInterface
{
    public function getAll()
    {
        return Collectors::all();
    }

    public function getByName(string $name)
    {
        return Collectors::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('main.collectors');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('collectors.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['collectors.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return Collectors::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return Collectors::create($value);
    }

    public function update(int $id, array $data)
    {
        return Collectors::whereId($id)->update($data);
    }
}
