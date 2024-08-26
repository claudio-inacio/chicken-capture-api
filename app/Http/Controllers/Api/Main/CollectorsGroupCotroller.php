<?php

namespace App\Http\Controllers\Api\Main;

use App\Helpers\FormatHelper;
use App\Http\Controllers\Controller;
use App\Interfaces\Main\ColletorsGroupRepositoryInterface;
use App\Interfaces\Main\CompanyGroupRepositoryInterface;
use Illuminate\Http\Request;

class CollectorsGroupCotroller extends Controller
{
    private ColletorsGroupRepositoryInterface $colletorsGroupRepository;

    public function __construct
    (
        ColletorsGroupRepositoryInterface $colletorsGroupRepository
    )
    {
        $this->colletorsGroupRepository = $colletorsGroupRepository;
    }

    public function register(Request $request) {
        $request->validate([
            'function_name' => 'required',
            'salary' => 'required',
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;
        $arrayData['salary'] = FormatHelper::moneyToUS($arrayData['salary']);

        return $this->colletorsGroupRepository->create($arrayData);
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->colletorsGroupRepository->findAll($selectConfig, $whereCriterious, $request->user()));
    }

    public function update(Request $request){
        $request->validate([
            'function_name' => 'required',
            'salary' => 'required',
            'collectors_group_id' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;
        $arrayData['salary'] = FormatHelper::moneyToUS($arrayData['salary']);

        return $this->colletorsGroupRepository->update($request->collectors_group_id, $arrayData);
    }

    public function enable(Request $request){
        $request->validate([
            'collectors_group_id' => 'required',
            'enabled' => 'required',
        ]);

        return $this->colletorsGroupRepository->enable($request->collectors_group_id, $request->enabled);
    }
}
