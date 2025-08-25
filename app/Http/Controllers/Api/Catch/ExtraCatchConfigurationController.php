<?php

namespace App\Http\Controllers\Api\Catch;

use App\Http\Controllers\Controller;
use App\Interfaces\Catch\CatchsConfigurationRespositoryInterface;
use App\Interfaces\Catch\ExtraCatchRepositoryInterface;
use Illuminate\Http\Request;

class ExtraCatchConfigurationController extends Controller
{
    private ExtraCatchRepositoryInterface $extraCatchRepository;

    public function __construct
    (
        ExtraCatchRepositoryInterface $extraCatchRepository
    )
    {
        $this->extraCatchRepository = $extraCatchRepository;
    }

    public function register(Request $request) {
        $request->validate([
            'loading_target' => 'required',
            'bonus_amount' => 'required',
            'catch_type_id' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;

        return $this->extraCatchRepository->create($arrayData);
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->extraCatchRepository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'loading_target' => 'required',
            'bonus_amount' => 'required',
            'catch_type_id' => 'required',
            'extra_catch_id' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;

        return $this->extraCatchRepository->update($request->extra_catch_id, $arrayData);
    }

    public function enable(Request $request){
        $request->validate([
            'enabled' => 'required',
            'extra_catch_id' => 'required'
        ]);

        return $this->extraCatchRepository->enable($request->extra_catch_id, $request->enabled);
    }

    public function select(Request $request){
        $request->validate([
            'extra_catch_id' => 'required'
        ]);

        return $this->extraCatchRepository->getById($request->extra_catch_id);
    }
}
