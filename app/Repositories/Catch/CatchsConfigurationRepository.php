<?php

namespace App\Repositories\Catch;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Catch\CatchsConfigurationRespositoryInterface;
use App\Models\Catch\CatchsConfiguration;
use Illuminate\Support\Facades\DB;

class CatchsConfigurationRepository implements CatchsConfigurationRespositoryInterface
{
    public function getAll()
    {
        return CatchsConfiguration::all();
    }

    public function getByName(string $name)
    {
        return CatchsConfiguration::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('catch.catchs_configuration');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('catchs_configuration.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['catchs_configuration.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return CatchsConfiguration::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return CatchsConfiguration::create($value);
    }

    public function update(int $id, array $data)
    {
        return CatchsConfiguration::whereId($id)->update($data);
    }
}
