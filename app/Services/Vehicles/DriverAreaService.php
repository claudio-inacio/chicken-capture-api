<?php

namespace App\Services\Vehicles;

use App\Enum\Financial\StatusEnum;
use App\Enum\Financial\TypeFinanceEnum;
use App\Helpers\FormatHelper;
use App\Models\Financial\FinancialAccounts;
use App\Models\Vehicles\DriverArea;
use App\Models\Vehicles\Vehicle;
use App\Services\Financial\FinancialService;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class DriverAreaService
{
    public static function create(array $arrayData): JsonResponse
    {
        DB::beginTransaction();
        $arrayData['maintenance_expenses'] = FormatHelper::brlTodecimal($arrayData['maintenance_expenses']);
        $maintenance['success'] = false;
        $fuel['success'] = false;
        try {
            $vehicle = Vehicle::whereId($arrayData['vehicle_id'])->first();
            $driverArea = DriverArea::where('vehicle_id', $arrayData['vehicle_id'])
                ->whereDay('created_at', '<', date('d'))
                ->first();

            if ($driverArea) {
                if (!$driverArea->daily_end_date || !$driverArea->daily_end_km) {
                    DB::rollBack();
                    return ResponseService::businessError('Por favor finalize o dia anterior antes de iniciar um novo dia!');
                }
            }

            if ($arrayData['maintenance_expenses'] != 0) {
                $maintenance = FinancialService::saveMaintenanceFinance($arrayData);
                if (!$maintenance['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($maintenance['message'], $maintenance['error']);
                }
            }
            if ($arrayData['total_supply_value'] != 0) {
                $fuel = FinancialService::saveFuelFinance($arrayData);
                if (!$fuel['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($fuel['message'], $fuel['error']);
                }
            }

            $driverArea = DriverArea::where('vehicle_id', $arrayData['vehicle_id'])->first();
            if ($driverArea){
                DriverArea::whereId($driverArea->id)->update($arrayData);
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

    public static function update(int $id, array $arrayData){
        DB::beginTransaction();
        unset($arrayData['driver_area_id']);
        $arrayData['total_supply_value'] = FormatHelper::brlTodecimal($arrayData['total_supply_value']);
        $arrayData['maintenance_expenses'] = FormatHelper::brlTodecimal($arrayData['maintenance_expenses']);
        try {
            $driverArea = DriverArea::whereId($id)->first();
            $driverArea->update($arrayData);

            $maintenance = FinancialAccounts::where('credential_id', $driverArea->credential_id)
                ->where('company_id', $driverArea->company_id)
                ->where('description', 'Despesas com manuntencao')
                ->update(['amount' =>  $arrayData['maintenance_expenses']]);

            if (!$maintenance)
                FinancialAccounts::create([
                    'description' => 'Despesas com manuntencao',
                    'amount' =>  $arrayData['maintenance_expenses'],
                    'type' => TypeFinanceEnum::TO_DISCOUNT,
                    'status_id' => StatusEnum::TO_DISCOUNT,
                    'due_date' => now(),
                    'credential_id' => $driverArea->credential_id,
                    'company_id' => $driverArea->company_id,
                ]);

            $fuel = FinancialAccounts::where('credential_id', $driverArea->credential_id)
                ->where('company_id', $driverArea->company_id)
                ->where('description', 'Despesas com combustivel')
                ->update(['amount' =>  $arrayData['total_supply_value']]);

            if (!$fuel)
                FinancialAccounts::create([
                    'description' => 'Despesas com combustivel',
                    'amount' =>  $arrayData['total_supply_value'],
                    'type' => TypeFinanceEnum::TO_DISCOUNT,
                    'status_id' => StatusEnum::TO_DISCOUNT,
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

    public static function finalize(int $id, array $arrayData){
        DB::beginTransaction();
        unset($arrayData['driver_area_id']);
        $arrayData['daily_end_date'] = FormatHelper::dateToUsTimeStamp($arrayData['daily_end_date']);
        $arrayData['total_supply_value'] = FormatHelper::brlTodecimal($arrayData['total_supply_value']);
        try {
            $driverArea = DriverArea::whereId($id)->first();

            if ($driverArea->daily_end_date)
                return ResponseService::businessError('Dia ja foi finalizado! inicie um novo dia ou atualize os dados do dia!');

            $driverArea->daily_end_km = $arrayData['daily_end_km'];
            $driverArea->daily_end_date = $arrayData['daily_end_date'];
            $driverArea->total_supply_value = $arrayData['total_supply_value'];
            $driverArea->liters_of_fuel = $arrayData['liters_of_fuel'];
            $driverArea->update();

            $arrayData['vehicle_id'] = $driverArea->vehicle_id;
            $arrayData['credential_id'] = $driverArea->credential_id;
            $arrayData['company_id'] = $driverArea->company_id;

            if ($arrayData['daily_end_km'] != 0) {
                $dailEnd = VehicleService::saveMileageVehicle($arrayData);
                if (!$dailEnd['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($dailEnd['message'], $dailEnd['error']);
                }
            }

            if ($arrayData['maintenance_expenses'] != 0) {
                $maintenance = FinancialService::saveMaintenanceFinance($arrayData);
                if (!$maintenance['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($maintenance['message'], $maintenance['error']);
                }
            }
            if ($arrayData['total_supply_value'] != 0) {
                $fuel = FinancialService::saveFuelFinance($arrayData);
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
}
