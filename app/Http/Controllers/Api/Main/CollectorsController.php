<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use App\Interfaces\Main\CollectorsRepositoryInterface;
use Illuminate\Http\Request;

class CollectorsController extends Controller
{
    private CollectorsRepositoryInterface $collectorsRepository;

    public function __construct
    (
        CollectorsRepositoryInterface $collectorsRepository
    )
    {
        $this->collectorsRepository = $collectorsRepository;
    }

    public function register(Request $request) {
        $request->validate([
            'quantity' => 'required',
            'collectors_group_id' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;

        return $this->collectorsRepository->create($arrayData);
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->collectorsRepository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'quantity' => 'required',
            'collectors_id' => 'required'
        ]);

        return $this->collectorsRepository->update($request->collectors_id, $request->all());
    }

    public function enable(Request $request){
        $request->validate([
            'collectors_id' => 'required',
            'enabled' => 'required',
        ]);

        return $this->collectorsRepository->enable($request->collectors_id, $request->enabled);
    }
}
