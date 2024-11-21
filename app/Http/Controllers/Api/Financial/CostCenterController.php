<?php

namespace App\Http\Controllers\Api\Financial;

use App\Enum\Financial\StatusEnum;
use App\Http\Controllers\Controller;
use App\Interfaces\Financial\CostCenterRepositoryInterface;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CostCenterController extends Controller
{
    private CostCenterRepositoryInterface $costCenterRepository;

    public function __construct
    (
        CostCenterRepositoryInterface $costCenterRepository
    )
    {
        $this->costCenterRepository = $costCenterRepository;
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required',
        ]);

        return $this->costCenterRepository->create($request->all());
    }

    public function list(Request $request): JsonResponse
    {
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->costCenterRepository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'name' => 'required',
            'cost_center_id' => 'required',
        ]);

        return $this->costCenterRepository->update($request->cost_center_id, $request->all());
    }

    public function enable(Request $request){
        $request->validate([
            'cost_center_id' => 'required',
            'enabled' => 'required',
        ]);

        return $this->costCenterRepository->enable($request->cost_center_id, $request->enabled);
    }
}
