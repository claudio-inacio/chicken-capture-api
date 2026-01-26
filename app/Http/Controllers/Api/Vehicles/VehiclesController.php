<?php

namespace App\Http\Controllers\Api\Vehicles;

use App\Enum\Authentication\AccessGroupEnum;
use App\Enum\Financial\StatusEnum;
use App\Enum\Financial\TypeFinanceEnum;
use App\Http\Controllers\Controller;
use App\Interfaces\Vehicles\VehiclesRepositoryInterface;
use App\Models\Credential;
use App\Models\Financial\FinancialAccounts;
use App\Models\Main\Units;
use App\Models\Vehicles\Vehicle;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SebastianBergmann\CodeCoverage\Report\Xml\Unit;

class VehiclesController extends Controller
{
    private VehiclesRepositoryInterface $vehiclesRepository;

    public function __construct
    (
        VehiclesRepositoryInterface $vehiclesRepository
    )
    {
        $this->vehiclesRepository = $vehiclesRepository;
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required',
            'plate_number' => 'required',
            'unit_id' => 'required',
            'mileage' => 'required',
            'driver_credential_id' => 'required'
        ]);

        $unit = Units::find($request->unit_id);
        if(!$unit) return ResponseService::businessError('Uindade nao encontrada.');

        $motorista = Credential::find($request->driver_credential_id);
        if (!$motorista || !in_array($motorista->access_group_id, [AccessGroupEnum::DRIVER, AccessGroupEnum::DRIVER_RESPONSIBLE])) {
            return ResponseService::businessError('Motorista não encontrado.');
        }

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;

        return $this->vehiclesRepository->create($arrayData);
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->vehiclesRepository->findAll($selectConfig, $whereCriterious));
    }

    public function reportMaintenances(Request $request): JsonResponse
    {
        $credential = $request->user();

        $vehicles = Vehicle::leftJoin(
            'vehicles.vehicle_maintenances',
            'vehicle_maintenances.vehicle_id',
            '=',
            'vehicle.id'
        )
            ->join('authentication.credential', 'credential.id', 'vehicle.driver_credential_id')
            ->join('authentication.person', 'person.id', 'credential.person_id')
            ->where('vehicle.company_id', $credential->company_id)
            ->select([
                'vehicle.*',
                'vehicle_maintenances.maintenance_mileage',
                'person.name as person_name',
                'person.phone_number as person_phone_number',
            ])
            ->orderByDesc('vehicle_maintenances.maintenance_mileage') // garante pegar a última
            ->get()
            ->groupBy('id'); // agrupa por veículo

        $needMaintenance = [];

        foreach ($vehicles as $vehicleGroup) {
            $vehicle = $vehicleGroup->first();

            $currentMileage = $vehicle->mileage;
            $lastMaintenanceMileage = $vehicle->maintenance_mileage ?? 0;

            $mileageSinceLastMaintenance = $currentMileage - $lastMaintenanceMileage;

            if ($mileageSinceLastMaintenance >= 20000) {
                $needMaintenance[] = [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_name' => $vehicle->name,
                    'vehicle_plate' => $vehicle->plate_number,
                    'driver' => $vehicle->person_name,
                    'driver_phone_number' => $vehicle->person_phone_number,
                    'current_mileage' => $currentMileage,
                    'last_maintenance_mileage' => $lastMaintenanceMileage,
                    'mileage_since_last_maintenance' => $mileageSinceLastMaintenance,
                ];
            }
        }

        return ResponseService::success('Lista de veículos obtida com sucesso!', $needMaintenance);
    }

    public function rankingExpenses(Request $request): JsonResponse
    {
        $credential = $request->user();
        $ranking = FinancialAccounts::query()
        ->join('vehicles.vehicle as v', 'v.id', '=', 'financial.financial_accounts.vehicle_id')
        ->where('financial.financial_accounts.company_id', $credential->company_id)
        ->where('financial.financial_accounts.type', TypeFinanceEnum::TO_DISCOUNT)
        ->where('financial.financial_accounts.status_id', StatusEnum::DISCOUNT)
        ->select([
            'v.id as vehicle_id',
            'v.name as vehicle_name',
            'v.plate_number',
        ])
        ->selectRaw('COUNT(financial.financial_accounts.id) as records_count')
        ->selectRaw('SUM(financial.financial_accounts.amount) as total_expenses')
        ->groupBy('v.id', 'v.name', 'v.plate_number')
        ->get();

        return ResponseService::success('Lista obtida com sucesso', $ranking);
    }

    public function update(Request $request){
        $request->validate([
            'name' => 'required',
            'plate_number' => 'required',
            'unit_id' => 'required',
            'vehicle_id' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;
        return $this->vehiclesRepository->update($request->vehicle_id, $arrayData);
    }

    public function enable(Request $request){
        $request->validate([
            'vehicle_id' => 'required',
            'enabled' => 'required',
        ]);

        return $this->vehiclesRepository->enable($request->vehicle_id, $request->enabled);
    }

    public function expenses(Request $request): JsonResponse
    {
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->vehiclesRepository->expenses($selectConfig, $whereCriterious));
    }
}
