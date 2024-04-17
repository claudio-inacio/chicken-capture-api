<?php

namespace App\Http\Controllers\Api\ContractingCompany;

use App\Http\Controllers\Controller;
use App\Interfaces\ContractingCompany\ContractingCompanyRepositoryInterface;
use Illuminate\Http\Request;

class ContractingCompanyController extends Controller
{
    private ContractingCompanyRepositoryInterface $contractingCompanyRepository;

    public function __construct
    (
        ContractingCompanyRepositoryInterface $contractingCompanyRepository
    )
    {
        $this->contractingCompanyRepository = $contractingCompanyRepository;
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;

        return $this->contractingCompanyRepository->create($arrayData);
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->contractingCompanyRepository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'name' => 'required',
            'contracting_company_id' => 'required'
        ]);

        return $this->contractingCompanyRepository->update($request->contracting_company_id, $request->all());
    }

    public function enable(Request $request){
        $request->validate([
            'contracting_company_id' => 'required',
            'enabled' => 'required'
        ]);

        return $this->contractingCompanyRepository->enable($request->contracting_company_id, $request->enabled);
    }
}
