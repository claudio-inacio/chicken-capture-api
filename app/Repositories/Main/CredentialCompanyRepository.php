<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Main\CredentialCompanyRepositoryInterface;
use App\Models\Main\CredentialCompany;
use App\Services\ResponseService;
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
        $query = DB::table('authentication.credential_company')
            ->join('main.company', 'company.id', '=', 'credential_company.company_id')
        ->join('authentication.credential', 'credential.id', '=', 'credential_company.credential_id');

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

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        try {
            $credentialCompany = CredentialCompany::where('credential_id', $value['credential_id'])
                ->where('company_id', $value['company_id'])
                ->first();
            if ($credentialCompany) return ResponseService::businessError('Compania da credencial ja cadastrado!');

            CredentialCompany::create($value);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar compania da credencial', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        unset($data['credential_company_id']);
        try {
            $credentialCompany = CredentialCompany::where('credential_id', $data['credential_id'])
                ->where('company_id', $data['company_id'])
                ->where('id', '<>', $id)
                ->first();
            if ($credentialCompany) return ResponseService::businessError('Compania da credencial ja cadastrado!');

            CredentialCompany::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em alterar compania da credencial', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            CredentialCompany::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar compania da credencial', $e->getMessage());
        }
    }
}
