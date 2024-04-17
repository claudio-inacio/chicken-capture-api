<?php

namespace App\Http\Controllers\Api\Catch;

use App\Http\Controllers\Controller;
use App\Interfaces\Catch\CatchTypeRespositoryInterface;
use Illuminate\Http\Request;

class CatchTypeController extends Controller
{
    private CatchTypeRespositoryInterface $catchTypeRespository;

    public function __construct
    (
        CatchTypeRespositoryInterface $catchTypeRespository
    )
    {
        $this->catchTypeRespository = $catchTypeRespository;
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required'
        ]);

        return $this->catchTypeRespository->create($request->all());
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->catchTypeRespository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'name' => 'required',
            'catch_type_id' => 'required'
        ]);

        return $this->catchTypeRespository->update($request->catch_type_id, $request->all());
    }

    public function enable(Request $request){
        $request->validate([
            'catch_type_id' => 'required',
            'enabled' => 'required'
        ]);

        return $this->catchTypeRespository->enable($request->catch_type_id, $request->enabled);
    }
}
