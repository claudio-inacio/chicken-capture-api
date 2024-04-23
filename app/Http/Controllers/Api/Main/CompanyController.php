<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use App\Interfaces\Main\CompanyRepositoryInterface;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    private CompanyRepositoryInterface $companyRepository;

    public function __construct
    (
        CompanyRepositoryInterface $companyRepository
    )
    {
        $this->companyRepository = $companyRepository;
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'phone' => 'required',
            'cnpj' => 'required',
            'email' => 'required',
            'company_group_id' => 'required',
        ]);

        return $this->companyRepository->create($request->all());
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->companyRepository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'phone' => 'required',
            'cnpj' => 'required',
            'email' => 'required',
            'company_group_id' => 'required',
            'company_id' => 'required'
        ]);

        return $this->companyRepository->update($request->company_id, $request->all());
    }

    public function enable(Request $request){
        $request->validate([
            'company_id' => 'required',
            'enabled' => 'required',
        ]);

        return $this->companyRepository->enable($request->company_id, $request->enabled);
    }
}
