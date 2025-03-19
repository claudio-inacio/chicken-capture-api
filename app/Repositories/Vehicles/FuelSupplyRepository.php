<?php

namespace App\Repositories\Vehicles;

use App\Enum\Financial\CostCenterIdEnum;
use App\Enum\Financial\StatusEnum;
use App\Enum\Financial\TableReferenceFinanceEnum;
use App\Enum\Financial\TypeFinanceEnum;
use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Vehicles\FuelSupplyRepositoryInterface;
use App\Models\Financial\FinancialAccounts;
use App\Models\Vehicles\FuelSupply;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class FuelSupplyRepository implements FuelSupplyRepositoryInterface
{
    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('vehicles.fuel_supply')
            ->join('vehicles.driver_area', 'driver_area.id', '=', 'fuel_supply.driver_area_id')
            ->join('vehicles.vehicle', 'vehicle.id', '=', 'driver_area.vehicle_id')
            ->join('authentication.credential', 'credential.id', '=', 'driver_area.credential_id')
            ->join('authentication.person', 'person.id', '=', 'credential.person_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('fuel_supply.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'fuel_supply.*',
            'vehicle.name as vehicle_name',
            'vehicle.plate_number as vehicle_plate',
            'person.name as person_name',
        ]);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return FuelSupply::where('id',$id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();
        try {
            $value['total_value'] = FormatHelper::brlTodecimal($value['total_value']);
            $fuelSupply = FuelSupply::create($value);

            FinancialAccounts::create([
                'description' => 'Despesas com combustivel',
                'cost_center_id' => CostCenterIdEnum::VEICULO,
                'amount' => $value['total_value'],
                'due_date' => now(),
                'reference_id' => $fuelSupply->id,
                'status_id' => StatusEnum::TO_DISCOUNT,
                'table_reference_id' => TableReferenceFinanceEnum::FUEL,
                'type' => TypeFinanceEnum::TO_DISCOUNT,
                'credential_id' => $value['credential_id'],
                'company_id' => $value['company_id']
            ]);

            DB::commit();
            return ResponseService::success204();
        } catch (\Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em registrar despesa', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();
        try {
            FinancialAccounts::where('reference_id', $id)
                ->where('table_reference_id', TableReferenceFinanceEnum::FUEL)
                ->update([
                'amount' => $data['total_value'],
            ]);

            FuelSupply::whereId($id)->update($data);

            DB::commit();
            return ResponseService::success204();
        } catch (\Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em alterar despesa', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();
        try {
            FinancialAccounts::where('table_reference_id', TableReferenceFinanceEnum::FUEL)
                ->where('reference_id', $id)
                ->update(['enabled' => $enable]);

            FuelSupply::whereId($id)->update(['enabled' => $enable]);

            DB::commit();
            return ResponseService::success204();
        } catch (\Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha Ativar/Desativar Despesa de combustivel', $e->getMessage());
        }
    }
}
