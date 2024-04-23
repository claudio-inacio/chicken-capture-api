<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Interfaces\Financial\MonthlyClosingReportsRepositoryInterface;
use App\Services\Financial\MonthlyClosingReportsService;
use Illuminate\Http\Request;

class MonthlyClosingReportsController extends Controller
{
    private MonthlyClosingReportsRepositoryInterface $monthlyClosingReportsRepository;

    public function __construct
    (
        MonthlyClosingReportsRepositoryInterface $monthlyClosingReportsRepository
    )
    {
        $this->monthlyClosingReportsRepository = $monthlyClosingReportsRepository;
    }

    public function register(Request $request) {
        $request->validate([
            'month' => 'required',
            'year' => 'required',
            'total_expenses' => 'required',
            'total_income' => 'required',
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;

        return MonthlyClosingReportsService::createOrUpdate($arrayData);
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->monthlyClosingReportsRepository->findAll($selectConfig, $whereCriterious));
    }
}
