<?php

namespace App\Services\Catch;

use App\Enum\Financial\TableReferenceFinanceEnum;
use App\Enum\Financial\TypeFinanceEnum;
use App\Helpers\FormatHelper;
use App\Models\Catch\CatchDaily;
use App\Models\Catch\CatchsCancelled;
use App\Models\Catch\CatchsConfiguration;
use App\Services\Financial\FinancialService;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CatchDailyService
{
    /**
     * @throws Exception
     */
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
            $cancelled = $catchCancelled->quantity;

            $totalQuantity = $catchDaily->quantity * $catchsConfiguration->catch_price;
            $totalValue = ($cancelled * $catchsConfiguration->cancellation_price) + $totalQuantity;

            $result = FinancialService::postAccountReceivable(
                $totalValue, $arrayData['credential_id'], $arrayData['company_id'],
                $catchDaily->id, TableReferenceFinanceEnum::DAILY_CATCH, $arrayData['team_id']
            );

            if(!$result['success']){
                DB::rollBack();
                return ResponseService::internalServerError($result['message'], $result['error']);
            }

            DB::commit();
            return ResponseService::success204();
        }catch (Exception $e){
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
        }catch (Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em atualizar apanha diaria', $e->getMessage());
        }
    }

    public static function analytics(array $arrayRequest, $user): JsonResponse
    {
        try {
            $startDate = FormatHelper::dateToUs($arrayRequest['start_date']);
            $endDate = FormatHelper::dateToUs($arrayRequest['end_date']);

            $catchDaily = DB::select("SELECT * FROM catch.catch_daily
                                         WHERE catch_daily.date >= '{$startDate}'
                                           AND catch_daily.date <= '{$endDate}'
                                           AND catch_daily.enabled = true
                                           AND catch_daily.company_id = '$user->company_id'
                                ");

            $totalCatch = 0;
            $totalCancelled = 0;
            $totalCactchValue = 0;
            $totalCancelledValue = 0;

            foreach ($catchDaily as $item){
                $catchConfiguration = CatchsConfiguration::where('catch_type_id', $item->catch_type_id)->first();
                $totalCactchValue = $totalCactchValue + ($item->quantity * $catchConfiguration->catch_price);
                $totalCatch = $totalCatch + $item->quantity;
                $catchCancelled = CatchsCancelled::where('catch_daily_id', $item->id)
                    ->where('enabled', true)
                    ->get();

                if($catchCancelled){
                    foreach ($catchCancelled as $cancelledItem){
                        $totalCancelledValue = $totalCancelledValue + ($cancelledItem->quantity * $catchConfiguration->cancellation_price);
                        $totalCancelled = $totalCancelled + $cancelledItem->quantity;
                    }
                }
            }

            $totalValue = $totalCactchValue + $totalCancelledValue;

            $analyticCatchDaily = [
                'total_catch' => [
                    'total' => $totalCatch,
                    'value' => FormatHelper::decimalToBr($totalCactchValue)
                ],

                'total_cancelled' => [
                    'total' => $totalCancelled,
                    'value' => FormatHelper::decimalToBr($totalCancelledValue)
                ],
                'total' =>[
                    'total' => $totalCatch + $totalCancelled,
                    'value' => FormatHelper::decimalToBr($totalValue)
                ]
            ];

            return ResponseService::success('Sucesso em listar analitico de apanhas', $analyticCatchDaily);
        } catch (Exception $e){
            return ResponseService::internalServerError('Falha em listar analitico de apanhas', $e->getMessage());
        }
    }
}
