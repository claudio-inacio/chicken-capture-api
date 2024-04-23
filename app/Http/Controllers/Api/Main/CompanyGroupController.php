<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use App\Interfaces\Main\CompanyGroupRepositoryInterface;
use Illuminate\Http\Request;

class CompanyGroupController extends Controller
{
    private CompanyGroupRepositoryInterface $companyGroupRepository;

    public function __construct
    (
        CompanyGroupRepositoryInterface $companyGroupRepository
    )
    {
        $this->companyGroupRepository = $companyGroupRepository;
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required',
        ]);

        return $this->companyGroupRepository->create($request->all());
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->companyGroupRepository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'name' => 'required',
            'company_group_id' => 'required'
        ]);

        return $this->companyGroupRepository->update($request->company_group_id, $request->all());
    }

    public function enable(Request $request){
        $request->validate([
            'company_group_id' => 'required',
            'enabled' => 'required',
        ]);

        return $this->companyGroupRepository->enable($request->company_group_id, $request->enabled);
    }
}
