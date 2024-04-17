<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Main\CredentialCompanyRepositoryInterface;
use App\Models\Main\CredentialCompany;
use Illuminate\Support\Facades\DB;

class CredentialCompanyRepository implements CredentialCompanyRepositoryInterface
{
    public function getAll()
    {
        return CredentialCompany::all();
    }

    public function getByName(string $name)
    {
        return CredentialCompany::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('main.credential_company');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('credential_company.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['credential_company.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return CredentialCompany::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return CredentialCompany::create($value);
    }

    public function update(int $id, array $data)
    {
        return CredentialCompany::whereId($id)->update($data);
    }
}
