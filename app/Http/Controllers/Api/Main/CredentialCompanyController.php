<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use App\Interfaces\Main\CredentialCompanyRepositoryInterface;
use Illuminate\Http\Request;

class CredentialCompanyController extends Controller
{
    private CredentialCompanyRepositoryInterface $credentialCompanyRepository;

    public function __construct
    (
        CredentialCompanyRepositoryInterface $credentialCompanyRepository
    )
    {
        $this->credentialCompanyRepository = $credentialCompanyRepository;
    }

    public function register(Request $request) {
        $request->validate([
            'credential_id' => 'required',
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;

        return $this->credentialCompanyRepository->create($arrayData);
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->credentialCompanyRepository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'credential_id' => 'required',
            'credential_company_id' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;

        return $this->credentialCompanyRepository->update($request->credential_company_id, $arrayData);
    }

    public function enable(Request $request){
        $request->validate([
            'credential_company_id' => 'required',
            'enabled' => 'required',
        ]);

        return $this->credentialCompanyRepository->enable($request->credential_company_id, $request->enabled);
    }
}
