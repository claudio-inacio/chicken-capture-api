<?php

namespace App\Repositories\Authentication;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Authentication\CredentialRepositoryInterface;
use App\Models\Credential;
use Illuminate\Support\Facades\DB;

class CredentialRepository implements CredentialRepositoryInterface
{
    public function getAll()
    {
        return Credential::all();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('authentication.credential');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('credential.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);

        $query->select('credential.*')->orderBy($selectConfig['orderBy'][0], $selectConfig['orderBy'][1]);
        if(!empty($selectConfig['limit']))
            $query->limit($selectConfig['limit']);
        return  [
            'data' => $query->get()->toArray(),
            'total' => $total,
        ];
    }


    public function getByCpf(string $cpf)
    {
        return Credential::where('document',$cpf)
            ->join('main.company', 'company.id', '=', 'credential.company_id')
            ->join('authentication.person', 'person.id', '=', 'credential.person_id')
            ->select([
                'credential.*', 'company.name as company_name',
                'person.name', 'person.is_owner', 'person.email', 'person.company_group_id',
                'person.phone_number'
            ])
            ->get();
    }

    public function getById(int $id)
    {
        return Credential::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return Credential::create($value);
    }

    public function update(int $id, array $data)
    {
        return Credential::whereId($id)->update($data);
    }
}
