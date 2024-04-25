<?php

namespace App\Http\Controllers\Api\Vehicles;

use App\Http\Controllers\Controller;
use App\Interfaces\Vehicles\DriverAreaRepositoryInterface;
use App\Services\Vehicles\DriverAreaService;
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

    public function register(Request $request) {
        $request->validate([
            'vehicle_id' => 'required',
            'liters_of_fuel' => 'required',
            'total_supply_value' => 'required',
            'maintenance_expenses' => 'required',
            'daily_start_time' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['credential_id'] = $request->user()->id;
        $arrayData['company_id'] = $request->user()->company_id;

        return DriverAreaService::create($arrayData);
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->driverAreaRepository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'vehicle_id' => 'required',
            'liters_of_fuel' => 'required',
            'total_supply_value' => 'required',
            'maintenance_expenses' => 'required',
            'daily_start_km' => 'required',
            'daily_start_time' => 'required',
            'driver_area_id' => 'required'
        ]);

        return DriverAreaService::update($request->driver_area_id, $request->all());
    }

    public function finalize(Request $request){
        $request->validate([
            'daily_end_km' => 'required',
            'daily_end_date' => 'required',
            'total_supply_value' => 'required',
            'liters_of_fuel' => 'required',
            'maintenance_expenses' => 'required',
            'driver_area_id' => 'required'
        ]);

        return DriverAreaService::finalize($request->driver_area_id, $request->all());
    }

    public function enable(Request $request){
        $request->validate([
            'driver_area_id' => 'required',
            'enabled' => 'required',
        ]);

        return $this->driverAreaRepository->enable($request->driver_area_id, $request->enabled);
    }

    public function analytic(Request $request){
        $request->validate([
            'start_date' => 'required',
            'end_date' => 'required'
        ]);

        return DriverAreaService::analytics($request->all(), $request->user());
    }
}
