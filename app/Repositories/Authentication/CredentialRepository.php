<?php

namespace App\Repositories\Authentication;

use App\Enum\Authentication\AccessGroupEnum;
use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Authentication\CredentialRepositoryInterface;
use App\Models\Credential;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;

class CredentialRepository implements CredentialRepositoryInterface
{
    public function getAll()
    {
        return Credential::all();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('authentication.credential')
            ->join('authentication.person', 'person.id', '=','credential.person_id')
            ->join('main.company', 'company.id', '=','credential.company_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('credential.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);

        $query->select([
            'credential.*',
            'company.name as company_name',
            'person.name'
        ])->orderBy($selectConfig['orderBy'][0], $selectConfig['orderBy'][1]);
        if(!empty($selectConfig['limit']))
            $query->limit($selectConfig['limit']);
        return  [
            'data' => $query->get()->toArray(),
            'total' => $total,
        ];
    }

    #[ArrayShape(['data' => "mixed", 'total' => "int"])]
    public function listAvailableDriver(Credential $credential): array
    {
        $query = DB::table('authentication.credential')
            ->join('authentication.person', 'person.id', '=', 'credential.person_id')
            ->leftJoin('main.team', 'credential.id', '=', 'team.motorista_credential_id')
            ->join('main.company', 'company.id', '=', 'credential.company_id')
            ->join('main.company_group', 'company_group.id', '=', 'person.company_group_id');

        // Adicionando filtro para restringir por empresa, se necessário
        if ($credential->access_group_id != AccessGroupEnum::DEVELOPER && $credential->access_group_id != AccessGroupEnum::ADMINISTRATIVE) {
            $query->where('credential.company_id', $credential->company_id);
        }

        // Subquery para filtrar diaristas que não têm seu ID presente em team.motorista_credential_id
        $subquery = DB::table('main.team')
            ->select('motorista_credential_id')
            ->whereNotNull('motorista_credential_id');

        // Filtro para excluir diaristas presentes na subquery
        $query->whereNotIn('credential.id', $subquery);

        // Filtrar apenas diaristas do grupo MOTORISTA
        $query->where('credential.access_group_id', AccessGroupEnum::DRIVER);

        // Contar o total antes de adicionar o filtro 'enabled'
        $total = $query->count('credential.id');

        // Filtrar por diaristas habilitados e selecionar as colunas necessárias
        $query->where('person.enabled', true)
            ->select([
                'credential.*',
                'person.*',
                'team.name as team_name',
                'company_group.name as company_group_name',
                'company.name as company_name',
            ]);

        // Obter os resultados
        $result = $query->get()->toArray();

        return [
            'data' => $result,
            'total' => $total
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
