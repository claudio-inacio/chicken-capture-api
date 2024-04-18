<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use App\Interfaces\Main\UnitsRepositoryInterface;
use Illuminate\Http\Request;

class UnitsController extends Controller
{
    private UnitsRepositoryInterface $unitsRepository;

    public function __construct
    (
        UnitsRepositoryInterface $unitsRepository
    )
    {
        $this->unitsRepository = $unitsRepository;
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required',
            'location' => 'required',
            'contracting_company_id' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;

        return $this->unitsRepository->create($arrayData);
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->unitsRepository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'name' => 'required',
            'integrated_id' => 'required'
        ]);

        return $this->unitsRepository->update($request->integrated_id, $request->all());
    }

    public function enable(Request $request){
        $request->validate([
            'integrated_id' => 'required',
            'enabled' => 'required'
        ]);

        return $this->unitsRepository->enable($request->integrated_id, $request->enabled);
    }
}
