<?php

namespace App\Repositories\Main;

use App\Enum\Authentication\AccessGroupEnum;
use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Main\DiaristGroupRepositoryInterface;
use App\Models\Main\DiaristGroup;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DiaristGroupRepository implements DiaristGroupRepositoryInterface
{
    public function getAll()
    {
        return DiaristGroup::all();
    }

    public function findAll($selectConfig, array $whereCriterious, $credential) : array
    {
        $query = DB::table('main.diarist_group')
            ->join('main.company', 'company.id', '=', 'diarist_group.company_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        if ($credential->access_group_id != AccessGroupEnum::DEVELOPER and $credential->access_group_id != AccessGroupEnum::ADMINISTRATIVE){
            $query->where('diarist_group.company_id', $credential->company_id);
        }

        $total = $query->count('diarist_group.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'diarist_group.*',
            'company.name as company_name'
        ]);

        $result = $query->get()->toArray();

        foreach ($result as $item){
            $item->daily = FormatHelper::decimalToBr($item->daily);
        }

        return [
            'data' => $result,
            'total' => $total,
        ];
    }


    public function create(array $arrayData): JsonResponse
    {
        try {
            $diaristGroup = DiaristGroup::where('function_name', $arrayData['function_name'])
                ->where('enabled', true)
                ->where('company_id', $arrayData['company_id'])
                ->first();

            if ($diaristGroup) return ResponseService::businessError('Ja existe um grupo de diaristas cadastrada com essa função');

            DiaristGroup::create($arrayData);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar grupo de diaristas', $e->getMessage());
        }
    }

    public function update(int $id, array $data): JsonResponse
    {
        unset($data['diarist_group_id']);
        try {
            $diaristGroup = DiaristGroup::where('function_name', $data['function_name'])
                ->where('id', '<>', $id)
                ->where('enabled', true)
                ->first();
            if ($diaristGroup) return ResponseService::businessError('Ja existe um grupo de diaristas cadastrada com essa função');

            DiaristGroup::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar grupo de diaristas', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): JsonResponse
    {
        try {
            DiaristGroup::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar grupo de diaristas', $e->getMessage());
        }
    }
}
