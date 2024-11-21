<?php

namespace App\Repositories\Financial;

use App\Enum\Financial\TableReferenceFinanceEnum;
use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Financial\CostCenterRepositoryInterface;
use App\Models\Catch\CatchDaily;
use App\Models\Financial\CostCenter;
use App\Services\ResponseService;
use Exception;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;

class CostCenterRepository implements CostCenterRepositoryInterface
{
    public function getAll()
    {
        return CostCenter::all();
    }
    /**
     * @throws Exception
     */
    #[ArrayShape(['data' => "mixed", 'total' => "int"])]
    public function findAll($selectConfig, array $whereCriterious): array
    {
        $query = DB::table('financial.cost_center')
        ->where('cost_center.enabled', true);

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('cost_center.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'cost_center.*'
        ]);

        $result = $query->get()->toArray();

        return [
            'data' => $result,
            'total' => $total
        ];
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        try {
            $value['name'] = strtoupper($value['name']);
            CostCenter::create($value);
            return ResponseService::success204();
        } catch (Exception $e) {
            return ResponseService::internalServerError('Falha em registrar centro de custo', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        unset($data['cost_center_id']);
        try {
            $data['name'] = strtoupper($data['name']);
            CostCenter::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (Exception $e) {
            return ResponseService::internalServerError('Falha em alterar centro de custo', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            CostCenter::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (Exception $e) {
            return ResponseService::internalServerError('Falha Ativar/Desativar centro de custo', $e->getMessage());
        }
    }
}
