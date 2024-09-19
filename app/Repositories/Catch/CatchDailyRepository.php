<?php

namespace App\Repositories\Catch;

use App\Enum\Financial\TableReferenceFinanceEnum;
use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Catch\CatchDailyRespositoryInterface;
use App\Models\Catch\CatchDaily;
use App\Models\Catch\CatchsCancelled;
use App\Models\Catch\CatchsConfiguration;
use App\Models\Financial\FinancialAccounts;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class CatchDailyRepository implements CatchDailyRespositoryInterface
{
    public function getAll()
    {
        return CatchDaily::all();
    }

    public function getByName(string $name)
    {
        return CatchDaily::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('catch.catch_daily')
            ->join('authentication.credential', 'credential.id', '=', 'catch_daily.credential_id')
            ->join('authentication.person', 'person.id', '=', 'credential.person_id')
            ->join('main.units', 'units.id', '=', 'catch_daily.units_id')
            ->join('main.integrated', 'integrated.id', '=', 'catch_daily.integrated_id')
            ->join('region.city', 'city.id', '=', 'integrated.city_id')
            ->join('main.company', 'company.id', '=', 'catch_daily.company_id')
            ->join('main.team', 'team.id', '=', 'catch_daily.team_id')
            ->join('catch.catch_type', 'catch_type.id', '=', 'catch_daily.catch_type_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('catch_daily.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'catch_daily.*',
            'person.name as credential_name', 'credential.document as credential_document',
            'units.name as unit_name', 'units.code as unit_code',
            'integrated.name as integrated_name',
            'city.id as city_id',
            'city.name as city_name',
            'city.uf as city_uf',
            'city.code as city_code',
            'company.name as company_name',
            'team.name as team_name',
            'catch_type.name as catch_type_name'
        ]);

        $result = $query->get();

        // Pré-carregar CatchsConfiguration e CatchsCancelled para evitar consultas repetidas
        // Etudar o que esse KeyBy() faz

        $catchConfigurations = CatchsConfiguration::whereIn('catch_type_id', $result->pluck('catch_type_id'))->get()->keyBy('catch_type_id');
        $catchCancelleds = CatchsCancelled::whereIn('catch_daily_id', $result->pluck('id'))->get()->groupBy('catch_daily_id');

        $totalValueCatch = 0;
        $totalValueCatchCancelled = 0;

        foreach ($result as $item) {
            $catchConfiguration = $catchConfigurations->get($item->catch_type_id);
            $cancelleds = $catchCancelleds->get($item->id, collect());

            $totalCancelled = $cancelleds->sum('quantity');
            $totalCatch = $item->quantity - $totalCancelled;

            $totalValueCatchCancelled += $catchConfiguration->cancellation_price * $totalCancelled;
            $totalValueCatch += $catchConfiguration->catch_price * $totalCatch;

            $item->total_cancelled = $totalCancelled;
        }

        return [
            'data' => $result->toArray(),
            'total' => $total,
            'total_value_catch' => "R$ " . FormatHelper::decimalToBr($totalValueCatch),
            'total_value_catch_cancelled' => "R$ " . FormatHelper::decimalToBr($totalValueCatchCancelled),
            'total_value' => "R$ " . FormatHelper::decimalToBr($totalValueCatch + $totalValueCatchCancelled)
        ];
    }


    public function getById(int $id)
    {
        return CatchDaily::where('id',$id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        $value['date'] = FormatHelper::dateToUs($value['date']);

        try {
            CatchDaily::create($value);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar apanha', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        $data['date'] = FormatHelper::dateToUs($data['date']);
        unset($data['catch_daily_id']);
        try {
            CatchDaily::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em alterar apanha', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            CatchDaily::whereId($id)->update(['enabled' => $enable]);

            FinancialAccounts::where('table_reference_id', TableReferenceFinanceEnum::DAILY_CATCH)
                ->where('reference_id', $id)
                ->update(['enabled' => $enable]);

            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar apanha', $e->getMessage());
        }
    }
}
