<?php

namespace App\Services\Catch;

use App\Helpers\FormatHelper;
use App\Models\Catch\CatchDaily;
use App\Models\Catch\CatchsCancelled;
use App\Models\Catch\CatchsConfiguration;
use App\Services\Financial\FinancialService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatchCancelledService
{
    public static function save(array $arrayData, object $catchDaily): array
    {
        try {
            return [
                'success' => true,
                'data' =>
                    CatchsCancelled::create([
                        'date' => date('Y-m-d'),
                        'credential_id' => $arrayData['credential_id'],
                        'company_id' => $arrayData['company_id'],
                        'quantity' => $arrayData['total_cancelled'],
                        'catch_daily_id' => $catchDaily->id,
                        'notes' => $arrayData['notes'],
                    ])
            ];
        } catch (\Exception $e){
           return [
               'success' => false,
               'message' => 'Falha em registrar apanhas canceladas',
               'error' => $e->getMessage()
           ] ;
        }
    }

    public static function update(array $arrayData, object $catchDaily): array
    {
        try {
            $catchCancelled = CatchsCancelled::where('catch_daily_id', $catchDaily->id)->first();
            $catchCancelled->update([
                'date' => date('Y-m-d'),
                'credential_id' => $catchDaily->credential_id,
                'company_id' => $catchDaily->company_id,
                'quantity' => $arrayData['total_cancelled'],
                'notes' => $arrayData['notes'],
            ]);

            return [
                'success' => true,
                'data' => $catchCancelled
            ];

        } catch (\Exception $e){
            return [
                'success' => false,
                'message' => 'Falha em atualizar apanhas canceladas',
                'error' => $e->getMessage()
            ] ;
        }
    }

    public static function calculeAndSave($arrayData){
        DB::beginTransaction();
        $arrayData['date'] = FormatHelper::dateToUs($arrayData['date']);
        try {
            $catchDaily = CatchDaily::whereId($arrayData['catch_daily_id'])->first();
            unset($arrayData['catch_daily_id']);

            $catchsConfiguration = CatchsConfiguration::where('catch_type_id', $catchDaily->catch_type_id)->first();

            $cancelled = 0;
            $olderCatchCancelled = CatchsCancelled::where('catch_daily_id', $catchDaily->id)->get();
            if($olderCatchCancelled)
                foreach ($olderCatchCancelled as $item){
                    $cancelled = $cancelled + $item->quantity;
                }

            $catchCancelled = CatchCancelledService::save($arrayData, $catchDaily);
            if (!$catchCancelled['success']){
                DB::rollBack();
                return ResponseService::internalServerError($catchCancelled['message'], $catchCancelled['error']);
            }

            $catchCancelled = $catchCancelled['data'];
            $cancelled = $cancelled + $catchCancelled->quantity;
            $totalQuantity = ($catchDaily->quantity - $cancelled) * $catchsConfiguration->catch_price;
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
