<?php

namespace App\Http\Controllers\Api\Vehicles;

use App\Http\Controllers\Controller;
use App\Interfaces\Vehicles\FuelSupplyRepositoryInterface;
use App\Models\Vehicles\DriverArea;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FuelSupplyController extends Controller
{
    private FuelSupplyRepositoryInterface $fuelSupplyRepository;

    public function __construct
    (
        FuelSupplyRepositoryInterface $fuelSupplyRepository
    )
    {
        $this->fuelSupplyRepository = $fuelSupplyRepository;
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'driver_area_id' => 'required',
            'total_value' => 'required',
            'liters_filled' => 'required',
            'km_filled' => 'required',
        ]);

        $driverArea = DriverArea::find($request->driver_area_id);
        if(!$driverArea) return ResponseService::businessError('Registro da area do motorista nao foi encontrado.');

        $arrayData = $request->all();
        $arrayData['credential_id'] = $request->user()->id;
        $arrayData['company_id'] = $request->user()->company_id;

        return $this->fuelSupplyRepository->create($arrayData);
    }

    public function list(Request $request): JsonResponse
    {
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->fuelSupplyRepository->findAll($selectConfig, $whereCriterious));
    }

    public function listByDate(Request $request): JsonResponse
    {
        $request->validate([
            'startDate' => 'required',
            'endDate' => 'required',
        ]);

        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);

        return response()->json($this->fuelSupplyRepository->findAllByDate($selectConfig, $request->startDate, $request->endDate));
    }

    public function update(Request $request){
        $request->validate([
            'driver_area_id' => 'required',
            'total_value' => 'required',
            'liters_filled' => 'required',
            'km_filled' => 'required',
            'fuel_supply_id' => 'required'
        ]);

        $arrayData = $request->all();
        unset($arrayData['fuel_supply_id']);
        return $this->fuelSupplyRepository->update($request->fuel_supply_id, $arrayData);
    }

    public function enable(Request $request){
        $request->validate([
            'fuel_supply_id' => 'required',
            'enabled' => 'required',
        ]);

        return $this->fuelSupplyRepository->enable($request->fuel_supply_id, $request->enabled);
    }
}
