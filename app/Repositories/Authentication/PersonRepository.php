<?php

namespace App\Repositories\Authentication;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Authentication\PersonRespositoryInterface;
use App\Models\Authentication\Person;
use App\Models\Credential;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class PersonRepository implements PersonRespositoryInterface
{
    public function getAll()
    {
        return Person::all();
    }

    public function getByName(string $name)
    {
        return Person::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('authentication.person')
            ->join('main.company_group', 'company_group.id', '=','person.company_group_id')
            ->join('authentication.credential', 'credential.person_id', '=', 'person.id')
            ->join('main.company', 'company.id', '=', 'credential.company_id');


        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('person.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['person.*',
            'company_group.name as company_group_name',
            'credential.document',
            'credential.access_group_id',
            'credential.document',
            'credential.id as credential_id',
            'credential.company_id as credential_company_id',
            'company.name as company_name'
        ]);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return Person::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return Person::create($value);
    }

    public function update(array $data): \Illuminate\Http\JsonResponse
    {
        $arrayCredential = $data['credential'];
        $credentialId = $arrayCredential['credential_id'];
        $arrayPerson = $data['person'];
        $personId = $arrayPerson['person_id'];

        $arrayCredential['document'] = FormatHelper::formatCnpjCpf($arrayCredential['document']);
        $arrayPerson['phone_number'] = FormatHelper::removeSpecialCaracterTel($arrayPerson['phone_number']);
        try {
            $verifyCredential = Credential::where('document', $arrayCredential['document'])
                ->where('id', '<>', $credentialId)
                ->where('person_id', '<>', $personId)
                ->first();

            if ($verifyCredential) return ResponseService::invalidArguments('Ja existe uma credêncial cadastrada com esse CPF para outra pessoa!');

            unset($arrayCredential['credential_id'], $arrayPerson['person_id']);
            Credential::whereId($credentialId)->update($arrayCredential);
            Person::whereId($personId)->update($arrayPerson);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em atualizar registro', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enabled): \Illuminate\Http\JsonResponse
    {
        try {
            Person::whereId($id)->update(['enabled' => $enabled]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em ativar/desativar registro', $e->getMessage());
        }
    }
}
