<?php

namespace App\Services\Vehicles;

use App\Enum\Authentication\AccessGroupEnum;
use App\Enum\Financial\CostCenterIdEnum;
use App\Enum\Financial\StatusEnum;
use App\Enum\Financial\TypeFinanceEnum;
use App\Helpers\FormatHelper;
use App\Models\Credential;
use App\Models\Financial\FinancialAccounts;
use App\Models\Main\Team;
use App\Models\Vehicles\DriverArea;
use App\Models\Vehicles\FuelSupply;
use App\Models\Vehicles\Vehicle;
use App\Services\Financial\FinancialService;
use App\Services\Main\LogService;
use App\Services\ResponseService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class DriverAreaService
{
    /**
     * @throws Exception
     */
    public static function create(array $arrayData, Vehicle $vehicle): JsonResponse
    {
        DB::beginTransaction();
        $arrayData['maintenance_expenses'] = FormatHelper::brlTodecimal($arrayData['maintenance_expenses']);
        $maintenance['success'] = false;
        $day = (int)date('d');
        $date = (new \DateTime(date('Y-m-d')))->format('Y-m')."-{$day}";
        $dateOut = $date." 23:59:00";
        $date = FormatHelper::dateToUsTimeStamp($date);

        $fuel['success'] = false;

        try {
            $vehicle->mileage = $arrayData['daily_start_km'];
            $vehicle->update();

            $driverArea = DriverArea::where('vehicle_id', $arrayData['vehicle_id'])
                ->join('vehicles.vehicle', 'vehicle.id', '=', 'driver_area.vehicle_id')
                ->whereBetween('driver_area.created_at', [$date, $dateOut])
                ->select(
                    'driver_area.*',
                    'vehicle.name as vehicle_name', 'vehicle.plate_number as vehicle_plate_number',
                )
                ->first();

            if ($driverArea) {
                if (!$driverArea->daily_end_date || !$driverArea->daily_end_km) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Por favor finalize o dia em aberto antes de iniciar um novo dia!',
                        'dayStarted' => true,
                        'driver_area' => $driverArea
                    ], 422);
                }
            }

            $team = Team::where('motorista_credential_id', $vehicle->motorista_credential_id)->first();
            if (!$team)
                return ResponseService::businessError('Time nao encontrado.');

            $driverAreaUpdate = DriverArea::where('vehicle_id', $arrayData['vehicle_id'])->first();
            if ($driverAreaUpdate){
                $dateCreatedAt = FormatHelper::dateToUs($driverAreaUpdate->created_at);
                if (date('Y-m-d') === $dateCreatedAt) {
                    $proofExpenses = $arrayData['proof_of_payment_expenses'];
                    $proofSupply = $arrayData['proof_of_payment_supply'];
                    unset($arrayData['proof_of_payment_expenses'], $arrayData['proof_of_payment_supply']);
                    DriverArea::whereId($driverAreaUpdate->id)->update($arrayData);

                    $fuelSuplly = FuelSupply::where('driver_area_id', $driverAreaUpdate->id)->first();

                    $fuelSuplly->update([
                        'credential_id' => $arrayData['credential_id'],
                        'total_value' => FormatHelper::brlTodecimal($arrayData['total_supply_value']),
                        'liters_filled' => $arrayData['liters_of_fuel'],
                        'km_filled' => $arrayData['daily_start_km'],
                    ]);

                    if ($arrayData['maintenance_expenses'] != 0) {
                        $maintenance = FinancialService::saveMaintenanceFinance(
                            $arrayData, $driverAreaUpdate->id, $team, $proofExpenses
                        );

                        if (!$maintenance['success']) {
                            DB::rollBack();
                            return ResponseService::businessError($maintenance['message'], $maintenance['error']);
                        }
                    }

                    if ($arrayData['total_supply_value'] != 0) {
                        $fuel = FinancialService::saveFuelFinance($arrayData, $fuelSuplly->id, $team, $proofSupply);
                        if (!$fuel['success']) {
                            DB::rollBack();
                            return ResponseService::businessError($fuel['message'], $fuel['error']);
                        }
                    }

                    DB::commit();
                    return ResponseService::success('Dados da area do motorista atualizados.', [
                        'driver_area_id' => $driverAreaUpdate->id
                    ]);
                }
            }

           $driverArea = DriverArea::create($arrayData);

            $fuelSuplly = FuelSupply::create([
                'driver_area_id' => $driverArea->id,
                'credential_id' => $arrayData['credential_id'],
                'total_value' => FormatHelper::brlTodecimal($arrayData['total_supply_value']),
                'liters_filled' => $arrayData['liters_of_fuel'],
                'km_filled' => $arrayData['daily_start_km']
            ]);

            if ($arrayData['maintenance_expenses'] != 0) {
                $maintenance = FinancialService::saveMaintenanceFinance($arrayData, $driverArea->id, $team, $arrayData['proof_of_payment_expenses']);
                if (!$maintenance['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($maintenance['message'], $maintenance['error']);
                }
            }

            if ($arrayData['total_supply_value'] != 0) {
                $fuel = FinancialService::saveFuelFinance($arrayData, $fuelSuplly->id, $team, $arrayData['proof_of_payment_supply']);
                if (!$fuel['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($fuel['message'], $fuel['error']);
                }
            }

            DB::commit();
            return ResponseService::success('Dados da area do registrados com sucesso.', [
                'driver_area_id' => $driverArea->id
            ]);
        } catch (Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em registrar area do motorista', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    public static function update(int $id, array $arrayData): JsonResponse
    {
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
                    'cost_center_id' => CostCenterIdEnum::VEICULO,
                    'amount' =>  $arrayData['maintenance_expenses'],
                    'type' => TypeFinanceEnum::TO_DISCOUNT,
                    'status_id' => StatusEnum::TO_DISCOUNT,
                    'due_date' => now(),
                    'vehicle_id' => $arrayData['vehicle_id'],
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
                    'cost_center_id' => CostCenterIdEnum::VEICULO,
                    'amount' =>  $arrayData['total_supply_value'],
                    'type' => TypeFinanceEnum::TO_DISCOUNT,
                    'status_id' => StatusEnum::TO_DISCOUNT,
                    'due_date' => now(),
                    'credential_id' => $driverArea->credential_id,
                    'company_id' => $driverArea->company_id,
                ]);

            DB::commit();
            return ResponseService::success204();
        } catch (Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em alterar area do motorista', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function finalize(int $id, array $arrayData): JsonResponse
    {
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
            $driverArea->total_supply_value = $driverArea->total_supply_value + $arrayData['total_supply_value'];
            $driverArea->liters_of_fuel = $arrayData['liters_of_fuel'];
            $driverArea->update();

            $arrayData['vehicle_id'] = $driverArea->vehicle_id;
            $arrayData['credential_id'] = $driverArea->credential_id;
            $arrayData['company_id'] = $driverArea->company_id;

            $vehicle = Vehicle::find($driverArea->vehicle_id);
            if(!$vehicle) return ResponseService::businessError('Veículo nao encontrado.');

            $team = Team::where('motorista_credential_id', $vehicle->motorista_credential_id)->first();

            if ($arrayData['daily_end_km'] != 0) {
                $dailEnd = VehicleService::saveMileageVehicle($arrayData);
                if (!$dailEnd['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($dailEnd['message'], $dailEnd['error']);
                }
            }

            $fuelSuplly = FuelSupply::create([
                'driver_area_id' => $driverArea->id,
                'credential_id' => $arrayData['credential_id'],
                'total_value' => FormatHelper::brlTodecimal($arrayData['total_supply_value']),
                'liters_filled' => $arrayData['liters_of_fuel'],
                'km_filled' => $arrayData['daily_end_km']
            ]);

            if ($arrayData['maintenance_expenses'] != 0) {
                $maintenance = FinancialService::saveMaintenanceFinance($arrayData, $id, $team, $arrayData['proof_of_payment_expenses']);
                if (!$maintenance['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($maintenance['message'], $maintenance['error']);
                }
            }
            if ($arrayData['total_supply_value'] != 0) {
                $fuel = FinancialService::saveFuelFinance($arrayData, $fuelSuplly->id, $team, $arrayData['proof_of_payment_supply']);
                if (!$fuel['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($fuel['message'], $fuel['error']);
                }
            }

            DB::commit();
            return ResponseService::success204();
        } catch (Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em finalizar area do motorista', $e->getMessage());
        }
    }

    public static function analytics(array $arrayRequest, $user): JsonResponse
    {
        try {
            $startDate = FormatHelper::dateToUsTimeStamp($arrayRequest['start_date']);
            $endDate = FormatHelper::dateToUsTimeStamp($arrayRequest['end_date']);

            $driverArea = Credential::where('credential.company_id', $user->company_id)
                ->join('authentication.person', 'person.id', '=', 'credential.person_id')
                ->leftJoin('vehicles.driver_area', function($join) use ($startDate, $endDate){
                    $join->on("driver_area.credential_id", "=", "credential.id")
                        ->whereBetween('driver_area.created_at', [$startDate, $endDate])
                        ->where('driver_area.enabled', true);
                })
                ->leftJoin('vehicles.vehicle', 'vehicle.id', '=', 'driver_area.vehicle_id')
                //->where('credential.access_group_id', AccessGroupEnum::DRIVER)
                ->select([
                    'driver_area.*',
                    'person.name as person_name',
                    'vehicle.name', 'vehicle.plate_number',
                    'credential.id as user_id', 'credential.document'
                ])
                ->get();

            $arrayDriverArea = [];
            $totalFuel = 0;
            foreach ($driverArea as $key => $item){
                $financialAccounts = FinancialAccounts::where('reference_id', $item->id)->get();
                $fuel = 0;
                $totalMaintenance = 0;
                foreach ($financialAccounts as $itemFinancial){
                    if($itemFinancial->description == 'Despesas com manuntencao')
                        $totalMaintenance = $totalMaintenance + $itemFinancial->amount;

                    if($itemFinancial->description == 'Despesas com combustivel')
                        $fuel = $fuel + $itemFinancial->amount;
                }
                $arrayDriverArea[$key] = [
                    'vehicle' => [
                        'name' => $item->name,
                        'plate_number' => $item->plate_number,
                        'driver' => $item->person_name,
                        'driver_document' => $item->document,
                    ],
                    'fuel' => FormatHelper::decimalToBr($fuel),
                    'maintenance_expenses' => FormatHelper::decimalToBr($totalMaintenance),
                    'daily_start_km' => $item->daily_start_km,
                    'daily_start_time' => $item->daily_start_time,
                    'daily_end_km' => $item->daily_end_km,
                    'daily_end_date' => $item->daily_end_date,
                    'enabled' => $item->enabled
                ];

                $totalFuel = $totalFuel + $fuel;
            }


            return ResponseService::success('Sucesso em listar analitico da area de motoristas', [
                "daily_start" => $arrayDriverArea,
                "total_fuel_expenditure" => FormatHelper::decimalToBr($totalFuel),
            ]);
        } catch (Exception $e){
            return ResponseService::internalServerError('Falha em listar analitico da area de motoristas', $e->getMessage());
        }
    }

    public static function initDayAnalytic(array $arrayRequest, $order, Credential $credential): JsonResponse
    {
        $orderBy = ['init_day', 'desc'];
        if ($order == 'by_not_init') {
            $orderBy = ['init_day', 'asc'];
        }

        try {
            $startDate = FormatHelper::dateToUsTimeStamp($arrayRequest['start_date']);
            $endDate = FormatHelper::dateToUsTimeStamp($arrayRequest['end_date']);

            $drivers = Credential::query()
                ->select(
                    'credential.id',
                    'credential.document',
                    'person.name',
                    'person.phone_number',
                    DB::raw("CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM vehicles.driver_area da
                        WHERE da.credential_id = credential.id
                          AND da.created_at BETWEEN '{$startDate}' AND '{$endDate}'
                          AND da.daily_start_time IS NOT NULL
                    ) THEN true
                    ELSE false
                END as init_day")
                )
                ->join('authentication.person', 'person.id', '=', 'credential.person_id')
                ->where('credential.access_group_id', AccessGroupEnum::DRIVER)
                ->where('person.enabled', true)
                ->where('credential.company_id', $credential->company_id)
                ->orderBy($orderBy[0], $orderBy[1])
                ->get();

            return ResponseService::success('Sucesso em obter dados.', $drivers);
        }catch (\Exception $exception){
            return ResponseService::businessError('Falha em obter dados', [
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }
    }

    public static function endDayAnalytic(array $arrayRequest, $order, Credential $credential): JsonResponse
    {
        $orderBy = ['end_day', 'desc']; // default: quem finalizou primeiro
        if ($order == 'by_not_end') {
            $orderBy = ['end_day', 'asc']; // quem não finalizou primeiro
        }

        try {
            $startDate = FormatHelper::dateToUsTimeStamp($arrayRequest['start_date']);
            $endDate = FormatHelper::dateToUsTimeStamp($arrayRequest['end_date']);

            $drivers = Credential::query()
                ->select(
                    'credential.id',
                    'credential.document',
                    'person.name',
                    'person.phone_number',
                    DB::raw("CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM vehicles.driver_area da
                        WHERE da.credential_id = credential.id
                          AND da.created_at BETWEEN '{$startDate}' AND '{$endDate}'
                          AND da.daily_end_date IS NOT NULL
                    ) THEN true
                    ELSE false
                END as end_day")
                )
                ->join('authentication.person', 'person.id', '=', 'credential.person_id')
                ->where('credential.access_group_id', AccessGroupEnum::DRIVER)
                ->where('person.enabled', true)
                ->where('credential.company_id', $credential->company_id)
                ->orderBy($orderBy[0], $orderBy[1])
                ->get();

            return ResponseService::success('Sucesso em obter dados.', $drivers);
        } catch (\Exception $exception) {
            return ResponseService::businessError('Falha em obter dados', [
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }
    }

    public static function avgInitDayTimeAnalytic(array $arrayRequest): JsonResponse
    {
        try {
            $startDate = $arrayRequest['start_date'];
            $endDate = $arrayRequest['end_date'];

            $report = Credential::query()
                ->select(
                    'credential.id',
                    'credential.document',
                    'person.name',
                    'person.phone_number',
                    DB::raw("AVG(EXTRACT(EPOCH FROM (da.daily_start_time - TIMESTAMP '{$startDate}'))) as avg_seconds")
                )
                ->join('authentication.person', 'person.id', '=', 'credential.person_id')
                ->join('vehicles.driver_area as da', 'da.credential_id', '=', 'credential.id')
                ->where('credential.access_group_id', AccessGroupEnum::DRIVER)
                ->where('person.enabled', true)
                ->whereBetween('da.daily_start_time', [$startDate, $endDate])
                ->groupBy('credential.id', 'credential.document', 'person.name', 'person.phone_number')
                ->get()
                ->map(function ($item) {
                    $item->avg_time_formatted = gmdate("H:i:s", (int)$item->avg_seconds);
                    $item->avg_days = intval($item->avg_seconds / 86400); // inteiro, se for menor que 1 fica 0
                    return $item;
                });

            return ResponseService::success('Sucesso em obter dados.', $report);
        } catch (\Exception $exception) {
            return ResponseService::businessError('Falha em obter dados', [
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }
    }
}
