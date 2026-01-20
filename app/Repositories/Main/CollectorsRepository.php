<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Main\CollectorsRepositoryInterface;
use App\Models\Main\Collectors;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class CollectorsRepository implements CollectorsRepositoryInterface
{
    public function getAll()
    {
        return Collectors::all();
    }

    public function getByName(string $name)
    {
        return Collectors::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('main.collectors')
            ->join('main.company', 'company.id', '=', 'collectors.company_id')
            ->join('main.collectors_group', 'collectors_group.id', '=', 'collectors.collectors_group_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('collectors.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'collectors.*',
            'company.name as company_name',
            'collectors_group.function_name as collectors_group_function_name',
            'collectors_group.salary as collectors_group_salary'
        ]);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return Collectors::where('id',$id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        try {
            $collectors = Collectors::create($value);
            return ResponseService::success('Coletores cadastrada com sucesso!', [
                'collectors' => $collectors->id
            ]);
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar coletores', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        unset($data['collectors_id']);
        try {
            Collectors::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em alterar coletores', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            Collectors::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar coletores', $e->getMessage());
        }
    }
}
