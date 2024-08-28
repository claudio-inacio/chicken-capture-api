<?php

namespace App\Http\Controllers\Api\Region;

use App\Http\Controllers\Controller;
use App\Interfaces\Financial\MonthlyClosingReportsRepositoryInterface;
use App\Interfaces\Region\CityRepositoryInterface;
use App\Services\Financial\MonthlyClosingReportsService;
use Illuminate\Http\Request;

class CityController extends Controller
{
    private CityRepositoryInterface $cityRepository;

    public function __construct
    (
        CityRepositoryInterface $cityRepository
    )
    {
        $this->cityRepository = $cityRepository;
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->cityRepository->findAll($selectConfig, $whereCriterious));
    }
}
