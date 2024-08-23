<?php

namespace App\Http\Controllers\Api\Vehicles;

use App\Http\Controllers\Controller;
use App\Interfaces\Vehicles\VehiclesRepositoryInterface;
use Illuminate\Http\Request;

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
        ]);

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
}
