<?php

namespace App\Repositories\ContractingCompany;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\ContractingCompany\IntegratedRepositoryInterface;
use App\Models\ContractingCompany\Integrated;
use Illuminate\Support\Facades\DB;

class IntegratedRepository implements IntegratedRepositoryInterface
{
    public function getAll()
    {
        return Integrated::all();
    }

    public function getByName(string $name)
    {
        return Integrated::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('contracting_company.integrated');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('integrated.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['integrated.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return Integrated::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return Integrated::create($value);
    }

    public function update(int $id, array $data)
    {
        return Integrated::whereId($id)->update($data);
    }
}
