<?php

namespace App\Repositories\Vehicles;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Vehicles\VehiclesRepositoryInterface;
use App\Models\Vehicles\Vehicles;
use Illuminate\Support\Facades\DB;

class VehiclesRepository implements VehiclesRepositoryInterface
{
    public function getAll()
    {
        return Vehicles::all();
    }

    public function getByName(string $name)
    {
        return Vehicles::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('vehicles.vehicles');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('vehicles.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['vehicles.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return Vehicles::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return Vehicles::create($value);
    }

    public function update(int $id, array $data)
    {
        return Vehicles::whereId($id)->update($data);
    }
}
