<?php

namespace App\Http\Controllers\Api\Main;

use App\Helpers\FormatHelper;
use App\Http\Controllers\Controller;
use App\Interfaces\Main\DiaristGroupRepositoryInterface;
use Illuminate\Http\Request;

class DiaristGroupController extends Controller
{
    private DiaristGroupRepositoryInterface $diaristGroupRepository;

    public function __construct
    (
        DiaristGroupRepositoryInterface $diaristGroupRepository
    )
    {
        $this->diaristGroupRepository = $diaristGroupRepository;
    }

    public function register(Request $request) {
        $request->validate([
            'function_name' => 'required',
            'daily' => 'required',
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;
        $arrayData['daily'] = FormatHelper::moneyToUS($arrayData['daily']);

        return $this->diaristGroupRepository->create($arrayData);
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->diaristGroupRepository->findAll($selectConfig, $whereCriterious, $request->user()));
    }

    public function update(Request $request){
        $request->validate([
            'function_name' => 'required',
            'daily' => 'required',
            'diarist_group_id' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;
        $arrayData['daily'] = FormatHelper::moneyToUS($arrayData['daily']);

        return $this->diaristGroupRepository->update($request->diarist_group_id, $arrayData);
    }

    public function enable(Request $request){
        $request->validate([
            'diarist_group_id' => 'required',
            'enabled' => 'required',
        ]);

        return $this->diaristGroupRepository->enable($request->diarist_group_id, $request->enabled);
    }
}
