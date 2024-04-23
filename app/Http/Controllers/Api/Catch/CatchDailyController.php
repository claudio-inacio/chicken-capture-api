<?php

namespace App\Http\Controllers\Api\Catch;

use App\Http\Controllers\Controller;
use App\Interfaces\Catch\CatchDailyRespositoryInterface;
use Illuminate\Http\Request;

class CatchDailyController extends Controller
{
    private CatchDailyRespositoryInterface $catchDailyRespository;

    public function __construct
    (
        CatchDailyRespositoryInterface $catchDailyRespository
    )
    {
        $this->catchDailyRespository = $catchDailyRespository;
    }

    public function register(Request $request) {
        $request->validate([
            'date' => 'required',
            'quantity' => 'required',
            'code' => 'required',
            'batch' => 'required',
            'total_cancelled' => 'required',
            'units_id' => 'required',
            'integrated_id' => 'required',
            'team_id' => 'required',
            'catch_type_id' => 'required',
        ]);

        $arrayData = $request->all();
        $arrayData['credential_id'] = $request->user()->id;
        $arrayData['company_id'] = $request->user()->company_id;

        return $this->catchDailyRespository->create($arrayData);
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->catchDailyRespository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'date' => 'required',
            'quantity' => 'required',
            'code' => 'required',
            'batch' => 'required',
            'total_cancelled' => 'required',
            'units_id' => 'required',
            'integrated_id' => 'required',
            'team_id' => 'required',
            'catch_type_id' => 'required',
            'catch_daily_id' => 'required'
        ]);

        return $this->catchDailyRespository->update($request->catch_daily_id, $request->all());
    }

    public function enable(Request $request){
        $request->validate([
            'catch_daily_id' => 'required',
            'enabled' => 'required'
        ]);

        return $this->catchDailyRespository->enable($request->catch_daily_id, $request->enabled);
    }
}
