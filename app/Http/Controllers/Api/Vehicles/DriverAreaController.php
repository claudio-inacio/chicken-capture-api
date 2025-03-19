<?php

namespace App\Http\Controllers\Api\Vehicles;

use App\Http\Controllers\Controller;
use App\Interfaces\Vehicles\DriverAreaRepositoryInterface;
use App\Models\Vehicles\Vehicle;
use App\Services\ResponseService;
use App\Services\Vehicles\DriverAreaService;
use Exception;
use Illuminate\Http\Request;

class DriverAreaController extends Controller
{
    private DriverAreaRepositoryInterface $driverAreaRepository;

    public function __construct
    (
        DriverAreaRepositoryInterface $driverAreaRepository
    )
    {
        $this->driverAreaRepository = $driverAreaRepository;
    }

    /**
     * @throws Exception
     */
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'vehicle_id' => 'required',
            'liters_of_fuel' => 'required',
            'total_supply_value' => 'required',
            'maintenance_expenses' => 'required',
            'daily_start_time' => 'required',
        ]);

        $vehicle = Vehicle::find($request->vehicle_id);
        if(!$vehicle) return ResponseService::businessError('Veículo nao encontrado.');

        $arrayData = $request->all();
        $arrayData['credential_id'] = $request->user()->id;
        $arrayData['company_id'] = $request->user()->company_id;

        if (!empty($arrayData['maintenance_expenses']) && $arrayData['maintenance_expenses'] > 0) {
            $request->validate(['proof_of_payment_expenses' => 'required']);
        }

        if (!empty($arrayData['total_supply_value']) && $arrayData['total_supply_value'] > 0) {
            $request->validate(['proof_of_payment_supply' => 'required']);
        }

        return DriverAreaService::create($arrayData, $vehicle);
    }

    public function list(Request $request): \Illuminate\Http\JsonResponse
    {
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->driverAreaRepository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'vehicle_id' => 'required',
            'liters_of_fuel' => 'required',
            'total_supply_value' => 'required',
            'maintenance_expenses' => 'required',
            'daily_start_km' => 'required',
            'daily_start_time' => 'required',
            'driver_area_id' => 'required'
        ]);

        $vehicle = Vehicle::find($request->vehicle_id);
        if(!$vehicle) return ResponseService::businessError('Veículo nao encontrado.');
        $arrayData = $request->all();
        $arrayData['motorista_credential_id'] = $vehicle->motorista_credential_id;

        return DriverAreaService::update($request->driver_area_id, $arrayData);
    }

    /**
     * @throws Exception
     */
    public function finalize(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'daily_end_km' => 'required',
            'daily_end_date' => 'required',
            'total_supply_value' => 'required',
            'liters_of_fuel' => 'required',
            'maintenance_expenses' => 'required',
            'driver_area_id' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['credential_id'] = $request->user()->id;

        if (!empty($arrayData['maintenance_expenses']) && $arrayData['maintenance_expenses'] > 0) {
            $request->validate(['proof_of_payment_expenses' => 'required']);
        }

        if (!empty($arrayData['total_supply_value']) && $arrayData['total_supply_value'] > 0) {
            $request->validate(['proof_of_payment_supply' => 'required']);
        }

        return DriverAreaService::finalize($request->driver_area_id, $arrayData);
    }

    public function enable(Request $request){
        $request->validate([
            'driver_area_id' => 'required',
            'enabled' => 'required',
        ]);

        return $this->driverAreaRepository->enable($request->driver_area_id, $request->enabled);
    }

    public function analytic(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'start_date' => 'required',
            'end_date' => 'required'
        ]);

        return DriverAreaService::analytics($request->all(), $request->user());
    }
}
