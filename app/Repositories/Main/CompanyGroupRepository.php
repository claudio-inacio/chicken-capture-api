<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Main\CompanyGroupRepositoryInterface;
use App\Models\Main\CompanyGroup;
use Illuminate\Support\Facades\DB;

class CompanyGroupRepository implements CompanyGroupRepositoryInterface
{
    public function getAll()
    {
        return CompanyGroup::all();
    }

    public function getByName(string $name)
    {
        return CompanyGroup::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('main.company_group');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('company_group.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['company_group.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return CompanyGroup::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return CompanyGroup::create($value);
    }

    public function update(int $id, array $data)
    {
        return CompanyGroup::whereId($id)->update($data);
    }
}
