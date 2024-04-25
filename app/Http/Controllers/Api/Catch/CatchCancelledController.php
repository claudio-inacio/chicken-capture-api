<?php

namespace App\Http\Controllers\Api\Catch;

use App\Http\Controllers\Controller;
use App\Interfaces\Catch\CatchsCancelledRespositoryInterface;
use App\Services\Catch\CatchCancelledService;
use Illuminate\Http\Request;

class CatchCancelledController extends Controller
{
    private CatchsCancelledRespositoryInterface $catchsCancelledRespository;

    public function __construct
    (
        CatchsCancelledRespositoryInterface $catchsCancelledRespository
    )
    {
        $this->catchsCancelledRespository = $catchsCancelledRespository;
    }

    public function register(Request $request) {
        $request->validate([
            'date' => 'required',
            'total_cancelled' => 'required',
            'catch_daily_id' => 'required',
            'notes' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['credential_id'] = $request->user()->id;
        $arrayData['company_id'] = $request->user()->company_id;

        return CatchCancelledService::calculeAndSave($arrayData);
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->catchsCancelledRespository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'date' => 'required',
            'quantity' => 'required',
            'catch_daily_id' => 'required',
            'notes' => 'required',
            'catchs_cancelled_id' => 'required'
        ]);

        return $this->catchsCancelledRespository->update($request->catchs_cancelled_id, $request->all());
    }

    public function enable(Request $request){
        $request->validate([
            'catch_cancelled_id' => 'required',
            'enabled' => 'required'
        ]);

        return $this->catchsCancelledRespository->enable($request->catch_cancelled_id, $request->enabled);
    }
}
