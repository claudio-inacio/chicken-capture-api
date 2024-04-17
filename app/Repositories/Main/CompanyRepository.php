<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Main\CompanyRepositoryInterface;
use App\Models\Main\Company;
use Illuminate\Support\Facades\DB;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function getAll()
    {
        return Company::all();
    }

    public function getByName(string $name)
    {
        return Company::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('main.company');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('company.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['company.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return Company::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return Company::create($value);
    }

    public function update(int $id, array $data)
    {
        return Company::whereId($id)->update($data);
    }
}
