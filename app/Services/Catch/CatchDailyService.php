<?php

namespace App\Services\Catch;

use App\Enum\Financial\TableReferenceFinanceEnum;
use App\Helpers\FormatHelper;
use App\Models\Catch\CatchDaily;
use App\Models\Catch\CatchsConfiguration;
use App\Services\Financial\FinancialService;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CatchDailyService
{
    public static function savefortnightFinance(array $arrayData): JsonResponse
    {
        DB::beginTransaction();
        $arrayData['date'] = FormatHelper::dateToUs($arrayData['date']);
        if ($arrayData['total_cancelled'] > 0){
            if (empty($arrayData['notes']))
                return ResponseService::businessError('é obrigatorio passar as notas se houver frango cancelado!');
        }
        try {
            $catchDaily = CatchDaily::create($arrayData);

            $catchsConfiguration = CatchsConfiguration::where('catch_type_id', $catchDaily->catch_type_id)->first();

            $catchCancelled = CatchCancelledService::save($arrayData, $catchDaily);
            if (!$catchCancelled['success']){
                DB::rollBack();
                return ResponseService::internalServerError($catchCancelled['message'], $catchCancelled['error']);
            }

            $catchCancelled = $catchCancelled['data'];

            $catchDaily->quantity = $catchDaily->quantity - $catchCancelled->quantity;
            $cancelled = $catchCancelled->quantity;

            $totalQuantity = $catchDaily->quantity * $catchsConfiguration->catch_price;
            $totalValue = ($cancelled * $catchsConfiguration->cancellation_price) + $totalQuantity;

            $result = FinancialService::postAccountReceivable(
                $totalValue, $arrayData['credential_id'], $arrayData['company_id'], $catchDaily->id, TableReferenceFinanceEnum::DAILY_CATCH
            );

            if(!$result['success']){
                DB::rollBack();
                return ResponseService::internalServerError($result['message'], $result['error']);
            }

            DB::commit();
            return ResponseService::success204();
        }catch (\Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em registrar apanha diaria', $e->getMessage());
        }
    }

    public static function updatefortnightFinance(int $catchDailyId, array $arrayData): JsonResponse
    {
        if ($arrayData['total_cancelled'] > 0){
            if (empty($arrayData['notes']))
                return ResponseService::businessError('é obrigatorio passar as notas se houver frango cancelado!');
        }

        DB::beginTransaction();
        unset($arrayData['catch_daily_id']);
        $arrayData['date'] = FormatHelper::dateToUs($arrayData['date']);
        try {
            $catchDaily = CatchDaily::whereId($catchDailyId)->first();
            $catchDaily->update($arrayData);

            $catchsConfiguration = CatchsConfiguration::where('catch_type_id', $catchDaily->catch_type_id)->first();

            $catchCancelled = CatchCancelledService::update($arrayData, $catchDaily);
            if (!$catchCancelled['success']){
                DB::rollBack();
                return ResponseService::internalServerError($catchCancelled['message'], $catchCancelled['error']);
            }

            $catchCancelled = $catchCancelled['data'];
            $catchDaily->quantity = $catchDaily->quantity - $catchCancelled->quantity;
            $cancelled = $catchCancelled->quantity;


            $totalQuantity = $catchDaily->quantity * $catchsConfiguration->catch_price;
            $totalValue = ($cancelled * $catchsConfiguration->cancellation_price) + $totalQuantity;

            $result = FinancialService::updateAccountReceivale($totalValue, $catchDaily->credential_id, $catchDaily->company_id, $catchDaily->id);

            if(!$result['success']){
                DB::rollBack();
                return ResponseService::internalServerError($result['message'], $result['error']);
            }

            DB::commit();
            return ResponseService::success204();
        }catch (\Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em atualizar apanha diaria', $e->getMessage());
        }
    }
}
