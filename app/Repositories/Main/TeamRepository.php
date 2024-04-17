<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Main\CredentialCompanyRepositoryInterface;
use App\Interfaces\Main\TeamRepositoryInterface;
use App\Models\Main\Team;
use Illuminate\Support\Facades\DB;

class TeamRepository implements TeamRepositoryInterface
{
    public function getAll()
    {
        return Team::all();
    }

    public function getByName(string $name)
    {
        return Team::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('main.team');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('team.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['team.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return Team::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return Team::create($value);
    }

    public function update(int $id, array $data)
    {
        return Team::whereId($id)->update($data);
    }
}
