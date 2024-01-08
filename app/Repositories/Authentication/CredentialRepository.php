<?php

namespace App\Repositories\Authentication;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Authentication\CredentialRepositoryInterface;
use App\Models\CredentialProposals;
use Illuminate\Support\Facades\DB;

class CredentialRepository implements CredentialRepositoryInterface
{
    public function getAll()
    {
        return CredentialProposals::all();
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
        return CredentialProposals::where('document',$cpf)
            ->join('main.company', 'company.id', '=', 'credential.company_id')
            ->select(['credential.*', 'company.name as company_name'])
            ->get();
    }

    public function getById(int $id)
    {
        return CredentialProposals::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return CredentialProposals::create($value);
    }

    public function update(int $id, array $data)
    {
        return CredentialProposals::whereId($id)->update($data);
    }
}
