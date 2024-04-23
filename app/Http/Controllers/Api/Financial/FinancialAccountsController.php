<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Interfaces\Financial\FinancialAccountsRepositoryInterface;
use Illuminate\Http\Request;

class FinancialAccountsController extends Controller
{
    private FinancialAccountsRepositoryInterface $financialAccountsRepository;

    public function __construct
    (
        FinancialAccountsRepositoryInterface $financialAccountsRepository
    )
    {
        $this->financialAccountsRepository = $financialAccountsRepository;
    }

    public function register(Request $request) {
        $request->validate([
            'description' => 'required',
            'amount' => 'required',
            'due_date' => 'required',
            'type' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;
        $arrayData['credential_id'] = $request->user()->id;

        return $this->financialAccountsRepository->create($arrayData);
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->financialAccountsRepository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'description' => 'required',
            'amount' => 'required',
            'due_date' => 'required',
            'type' => 'required',
            'financial_accounts_id' => 'required'
        ]);

        return $this->financialAccountsRepository->update($request->financial_accounts_id, $request->all());
    }

    public function enable(Request $request){
        $request->validate([
            'financial_accounts_id' => 'required',
            'enabled' => 'required',
        ]);

        return $this->financialAccountsRepository->enable($request->financial_accounts_id, $request->enabled);
    }
}
