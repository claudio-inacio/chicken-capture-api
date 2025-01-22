<?php

namespace App\Repositories\Main;

use App\Enum\Authentication\AccessGroupEnum;
use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Main\ColletorsGroupRepositoryInterface;
use App\Models\Main\CollectorsGroup;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ColletorsGroupRepository implements ColletorsGroupRepositoryInterface
{
    public function getAll()
    {
        return CollectorsGroup::all();
    }

    public function findAll($selectConfig, array $whereCriterious, $credential) : array
    {
        $query = DB::table('main.collectors_group')
            ->join('main.company', 'company.id', '=', 'collectors_group.company_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        if ($credential->access_group_id != AccessGroupEnum::DEVELOPER and $credential->access_group_id != AccessGroupEnum::ADMINISTRATIVE){
            $query->where('collectors_group.company_id', $credential->company_id);
        }

        $total = $query->count('collectors_group.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'collectors_group.*',
            'company.name as company_name'
        ]);

        $result = $query->get()->toArray();

        foreach ($result as $item){
            $item->salary = FormatHelper::decimalToBr($item->salary);
        }

        return [
            'data' => $result,
            'total' => $total,
        ];
    }


    public function create(array $arrayData): JsonResponse
    {
        try {
            $collectorsGroup = CollectorsGroup::where('function_name', $arrayData['function_name'])
                ->where('enabled', true)
                ->where('company_id', $arrayData['company_id'])
                ->first();

            if ($collectorsGroup) return ResponseService::businessError('Ja existe um grupo de coletores cadastrada com essa função');

            CollectorsGroup::create($arrayData);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar grupo de coletores', $e->getMessage());
        }
    }

    public function update(int $id, array $data): JsonResponse
    {
        unset($data['collectors_group_id']);
        try {
            $collectorsGroup = CollectorsGroup::where('function_name', $data['function_name'])
                ->where('id', '<>', $id)
                ->where('enabled', true)
                ->first();
            if ($collectorsGroup) return ResponseService::businessError('Ja existe um grupo de coletores cadastrada com essa função');

            CollectorsGroup::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar grupo de coletores', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): JsonResponse
    {
        try {
            CollectorsGroup::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar grupo de coletores', $e->getMessage());
        }
    }
}
