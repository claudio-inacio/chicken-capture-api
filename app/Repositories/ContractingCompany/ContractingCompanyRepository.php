<?php

namespace App\Repositories\ContractingCompany;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\ContractingCompany\ContractingCompanyRepositoryInterface;
use App\Models\ContractingCompany\ContractingCompany;
use Illuminate\Support\Facades\DB;

class ContractingCompanyRepository implements ContractingCompanyRepositoryInterface
{
    public function getAll()
    {
        return ContractingCompany::all();
    }

    public function getByName(string $name)
    {
        return ContractingCompany::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('contracting_company.contracting_company');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('contracting_company.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['contracting_company.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return ContractingCompany::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return ContractingCompany::create($value);
    }

    public function update(int $id, array $data)
    {
        return ContractingCompany::whereId($id)->update($data);
    }
}
