<?php

namespace App\Http\Controllers\Api\Catch;

use App\Http\Controllers\Controller;
use App\Interfaces\Catch\CatchsConfigurationRespositoryInterface;
use Illuminate\Http\Request;

class CatchConfigurationController extends Controller
{
    private CatchsConfigurationRespositoryInterface $catchsConfigurationRespository;

    public function __construct
    (
        CatchsConfigurationRespositoryInterface $catchsConfigurationRespository
    )
    {
        $this->catchsConfigurationRespository = $catchsConfigurationRespository;
    }

    public function register(Request $request) {
        $request->validate([
            'catch_price' => 'required',
            'cancellation_price' => 'required',
            'catch_type_id' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;

        return $this->catchsConfigurationRespository->create($arrayData);
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->catchsConfigurationRespository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'catch_price' => 'required',
            'cancellation_price' => 'required',
            'catch_type_id' => 'required',
            'catchs_configuration_id' => 'required'
        ]);

        return $this->catchsConfigurationRespository->update($request->catchs_configuration_id, $request->all());
    }
}
