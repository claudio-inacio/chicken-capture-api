<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Main\ContractingCompanyRepositoryInterface;
use App\Models\Main\ContractingCompany;
use App\Services\ResponseService;
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
        $query = DB::table('main.contracting_company')
            ->join('main.company', 'company.id', '=', 'contracting_company.company_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('contracting_company.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['contracting_company.*', 'company.name as company_name']);

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

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        try {
            $contractingCompany = ContractingCompany::where('name', $value['name'])
                ->where('enabled', true)
                ->where('company_id', $value['company_id'])
                ->first();

            if ($contractingCompany) return ResponseService::businessError('Ja existe uma compania contratante com esse nome!');

            ContractingCompany::create($value);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar compania contratante', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        unset($data['contracting_company_id']);
        try {
            $contractingCompany = ContractingCompany::where('name', $data['name'])
                ->where('id', '<>', $id)->first();

            if ($contractingCompany) return ResponseService::businessError('Ja existe uma compania contratante com esse nome!');

            ContractingCompany::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em alterar compania contratante', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            ContractingCompany::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar compania contratante', $e->getMessage());
        }
    }
}
