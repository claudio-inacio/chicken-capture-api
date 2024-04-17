<?php

namespace App\Repositories\Authentication;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Authentication\AccessGroupRespositoryInterface;
use App\Models\Authentication\AccessGroup;
use Illuminate\Support\Facades\DB;

class AccessGroupRepository implements AccessGroupRespositoryInterface
{
    public function getAll()
    {
        return AccessGroup::all();
    }

    public function getByName(string $name)
    {
        return AccessGroup::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('authentication.access_group');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('access_group.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['access_group.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return AccessGroup::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return AccessGroup::create($value);
    }

    public function update(int $id, array $data)
    {
        return AccessGroup::whereId($id)->update($data);
    }
}
