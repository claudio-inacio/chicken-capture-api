<?php

namespace App\Repositories\Vehicles;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Vehicles\DriverAreaRepositoryInterface;
use App\Models\Financial\FinancialAccounts;
use App\Models\Vehicles\DriverArea;
use App\Models\Vehicles\Vehicle;
use App\Services\ResponseService;
use App\Services\Vehicles\DriverAreaService;
use Illuminate\Support\Facades\DB;

class DriverAreaRepository implements DriverAreaRepositoryInterface
{
    public function getAll()
    {
        return DriverArea::all();
    }

    public function getByName(string $name)
    {
        return DriverArea::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('vehicles.driver_area');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('driver_area.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['driver_area.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return DriverArea::where('id',$id)->get();
    }

    public function createOrUpdate(array $value): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();
        $value['maintenance_expenses'] = FormatHelper::brlTodecimal($value['maintenance_expenses']);
        $maintenance['success'] = false;
        $fuel['success'] = false;
        try {
            $vehicle = Vehicle::whereId($value['vehicle_id'])->first();
            $driverArea = DriverArea::where('vehicle_id', $value['vehicle_id'])
                ->whereDay('created_at', '<', date('d'))
                ->first();

            if ($driverArea) {
                if (!$driverArea->daily_end_date || !$driverArea->daily_end_km) {
                    DB::rollBack();
                    return ResponseService::businessError('Por favor finalize o dia anterior antes de iniciar um novo dia!');
                }
            }

            if ($value['maintenance_expenses'] != 0) {
                $maintenance = DriverAreaService::saveMaintenanceFinance($value);
                if (!$maintenance['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($maintenance['message'], $maintenance['error']);
                }
            }
            if ($value['total_supply_value'] != 0) {
                $fuel = DriverAreaService::saveFuelFinance($value);
                if (!$fuel['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($fuel['message'], $fuel['error']);
                }
            }

            $driverArea = DriverArea::where('vehicle_id', $value['vehicle_id'])->first();
            if ($driverArea){
                DriverArea::whereId($driverArea->id)->update($value);
                DB::commit();
                return ResponseService::success204();
            }

            $value['daily_start_km'] = $vehicle->mileage;
            DriverArea::create($value);

            DB::commit();
            return ResponseService::success204();
        } catch (\Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em registrar area do motorista', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();
        unset($data['driver_area_id']);
        $data['total_supply_value'] = FormatHelper::brlTodecimal($data['total_supply_value']);
        $data['maintenance_expenses'] = FormatHelper::brlTodecimal($data['maintenance_expenses']);
        try {
            $driverArea = DriverArea::whereId($id)->first();
            $driverArea->update($data);

            $maintenance = FinancialAccounts::where('credential_id', $driverArea->credential_id)
                ->where('company_id', $driverArea->company_id)
                ->where('description', 'Despesas com manuntencao')
                ->update(['amount' =>  $data['maintenance_expenses']]);

            if (!$maintenance)
                FinancialAccounts::create([
                    'description' => 'Despesas com manuntencao',
                    'amount' =>  $data['maintenance_expenses'],
                    'due_date' => now(),
                    'credential_id' => $driverArea->credential_id,
                    'company_id' => $driverArea->company_id,
                ]);

           $fuel = FinancialAccounts::where('credential_id', $driverArea->credential_id)
                ->where('company_id', $driverArea->company_id)
                ->where('description', 'Despesas com combustivel')
                ->update(['amount' =>  $data['total_supply_value']]);

            if (!$fuel)
                FinancialAccounts::create([
                    'description' => 'Despesas com combustivel',
                    'amount' =>  $data['total_supply_value'],
                    'due_date' => now(),
                    'credential_id' => $driverArea->credential_id,
                    'company_id' => $driverArea->company_id,
                ]);

            DB::commit();
            return ResponseService::success204();
        } catch (\Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em alterar area do motorista', $e->getMessage());
        }
    }

    public function finalize(int $id, array $data)
    {
        DB::beginTransaction();
        unset($data['driver_area_id']);
        $data['daily_end_date'] = FormatHelper::dateToUsTimeStamp($data['daily_end_date']);
        $data['total_supply_value'] = FormatHelper::brlTodecimal($data['total_supply_value']);
        try {
            $driverArea = DriverArea::whereId($id)->first();

            if ($driverArea->daily_end_date)
                return ResponseService::businessError('Dia ja foi finalizado! inicie um novo dia ou atualize os dados do dia!');

            $driverArea->daily_end_km = $data['daily_end_km'];
            $driverArea->daily_end_date = $data['daily_end_date'];
            $driverArea->total_supply_value = $data['total_supply_value'];
            $driverArea->liters_of_fuel = $data['liters_of_fuel'];
            $driverArea->update();

            $data['vehicle_id'] = $driverArea->vehicle_id;
            $data['credential_id'] = $driverArea->credential_id;
            $data['company_id'] = $driverArea->company_id;

            if ($data['daily_end_km'] != 0) {
                $dailEnd = DriverAreaService::saveMileageVehicle($data);
                if (!$dailEnd['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($dailEnd['message'], $dailEnd['error']);
                }
            }

            if ($data['maintenance_expenses'] != 0) {
                $maintenance = DriverAreaService::saveMaintenanceFinance($data);
                if (!$maintenance['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($maintenance['message'], $maintenance['error']);
                }
            }
            if ($data['total_supply_value'] != 0) {
                $fuel = DriverAreaService::saveFuelFinance($data);
                if (!$fuel['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($fuel['message'], $fuel['error']);
                }
            }

            DB::commit();
            return ResponseService::success204();
        } catch (\Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em finalizar area do motorista', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            DriverArea::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar area do motorista', $e->getMessage());
        }
    }
}
