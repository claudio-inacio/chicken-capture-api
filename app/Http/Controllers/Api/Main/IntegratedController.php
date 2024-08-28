<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use App\Interfaces\Main\IntegratedRepositoryInterface;
use Illuminate\Http\Request;

class IntegratedController extends Controller
{
    private IntegratedRepositoryInterface $integratedRepository;

    public function __construct
    (
        IntegratedRepositoryInterface $integratedRepository
    )
    {
        $this->integratedRepository = $integratedRepository;
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required',
            'city_id' => 'required'
        ]);

        return $this->integratedRepository->create($request->all());
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->integratedRepository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'name' => 'required',
            'city_id' => 'required',
            'integrated_id' => 'required'
        ]);

        return $this->integratedRepository->update($request->integrated_id, $request->all());
    }

    public function enable(Request $request){
        $request->validate([
            'integrated_id' => 'required',
            'enabled' => 'required'
        ]);

        return $this->integratedRepository->enable($request->integrated_id, $request->enabled);
    }
}
