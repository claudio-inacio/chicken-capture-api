<?php

namespace App\Repositories\Vehicles;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Vehicles\DriverAreaRepositoryInterface;
use App\Models\Vehicles\DriverArea;
use Illuminate\Support\Facades\DB;

class DriverAreaRepository implements DriverAreaRepositoryInterface
{
    public function getAll()
    {
        return DriverArea::all();
    }

    public function getByName(string $name)
    {
        return DriverArea::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('vehicles.driver_area');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('driver_area.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['driver_area.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return DriverArea::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return DriverArea::create($value);
    }

    public function update(int $id, array $data)
    {
        return DriverArea::whereId($id)->update($data);
    }
}
