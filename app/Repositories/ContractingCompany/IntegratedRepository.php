<?php

namespace App\Repositories\ContractingCompany;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\ContractingCompany\IntegratedRepositoryInterface;
use App\Models\ContractingCompany\Integrated;
use App\Services\ResponseService;
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
        $query = DB::table('contracting_company.integrated')
            ->join('contracting_company.contracting_company', 'contracting_company.id', '=', 'integrated.contracting_company_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('integrated.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['integrated.*', 'contracting_company.name as contracting_company_name']);

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

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        try {
            $integrated = Integrated::where('name', $value['name'])->first();
            if ($integrated) return ResponseService::businessError('Ja existe uma integraçao com esse nome!');

            Integrated::create($value);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar integraçao', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        unset($data['integrated_id']);
        try {
            $integrated = Integrated::where('name', $data['name'])
                ->where('id', '<>', $id)->first();

            if ($integrated) return ResponseService::businessError('Ja existe uma integraçao com esse nome!');

            Integrated::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em alterar integraçao', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            Integrated::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar integraçao', $e->getMessage());
        }
    }
}
