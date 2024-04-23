<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Main\CompanyGroupRepositoryInterface;
use App\Models\Main\CompanyGroup;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class CompanyGroupRepository implements CompanyGroupRepositoryInterface
{
    public function getAll()
    {
        return CompanyGroup::all();
    }

    public function getByName(string $name)
    {
        return CompanyGroup::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('main.company_group');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('company_group.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['company_group.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return CompanyGroup::where('id',$id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        try {
            $companyGroup = CompanyGroup::where('name', $value['name'])->first();
            if ($companyGroup) return ResponseService::businessError('Ja existe um grupo de compania com esse nome');

            CompanyGroup::create($value);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar grupo de compania', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        unset($data['company_group_id']);
        try {
            $companyGroup = CompanyGroup::where('name', $data['name'])
                ->where('id', '<>', $id)
                ->first();
            if ($companyGroup) return ResponseService::businessError('Ja existe um grupo de compania com esse nome');

            CompanyGroup::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em alterar grupo de compania', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            CompanyGroup::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar grupo de compania', $e->getMessage());
        }
    }
}
