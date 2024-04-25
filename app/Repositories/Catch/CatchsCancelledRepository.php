<?php

namespace App\Repositories\Catch;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Catch\CatchsCancelledRespositoryInterface;
use App\Models\Catch\CatchDaily;
use App\Models\Catch\CatchsCancelled;
use App\Models\Catch\CatchsConfiguration;
use App\Models\Financial\FinancialAccounts;
use App\Services\Financial\FinancialService;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class CatchsCancelledRepository implements CatchsCancelledRespositoryInterface
{
    public function getAll()
    {
        return CatchsCancelled::all();
    }

    public function getByName(string $name)
    {
        return CatchsCancelled::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('catch.catchs_cancelled');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('catchs_cancelled.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['catchs_cancelled.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return CatchsCancelled::where('id',$id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        $value['date'] = FormatHelper::dateToUs($value['date']);

        try {
            CatchsCancelled::create($value);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar cancelamento de apanha', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        $data['date'] = FormatHelper::dateToUs($data['date']);
        unset($data['catchs_cancelled_id']);
        try {
            CatchsCancelled::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em alterar cancelamento de apanha', $e->getMessage());
        }
    }

   public function enable(int $id, bool $enable)
   {
       DB::beginTransaction();
       try {
           $catchCancelled = CatchsCancelled::whereId($id)->first();
           $catchDaily = CatchDaily::whereId($catchCancelled->catch_daily_id)->first();
           $catchsConfiguration = CatchsConfiguration::where('catch_type_id', $catchDaily->catch_type_id)->first();

           if ($catchCancelled->enabled != $enable) {
               $catchCancelled->update(['enabled' => $enable]);
               $totalCancelled = 0;
               $catchsCancelled = CatchsCancelled::where('catch_daily_id', $catchDaily->id)
                   ->where('enabled', true)
                   ->get();

               foreach ($catchsCancelled as $item) {
                   $totalCancelled = $totalCancelled + $item->quantity;
               }

               $catchDaily->quantity = $catchDaily->quantity - $totalCancelled;
               $totalQuantity = $catchDaily->quantity * $catchsConfiguration->catch_price;
               $totalValue = ($totalCancelled * $catchsConfiguration->cancellation_price) + $totalQuantity;

               FinancialService::updateAccountReceivale($totalValue, $catchDaily->credential_id, $catchDaily->company_id, $catchDaily->id);
               DB::commit();
               return ResponseService::success204();
           }

           DB::commit();
           return ResponseService::success204();
       } catch (\Exception $e){
           return ResponseService::internalServerError('Falha em alterar cancelamento de apanha', $e->getMessage());
       }
   }
}
