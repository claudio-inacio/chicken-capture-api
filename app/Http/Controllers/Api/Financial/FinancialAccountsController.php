<?php

namespace App\Http\Controllers\Api\Financial;

use App\Enum\Financial\StatusEnum;
use App\Enum\Financial\TableReferenceFinanceEnum;
use App\Http\Controllers\Controller;
use App\Interfaces\Financial\FinancialAccountsRepositoryInterface;
use App\Models\Vehicles\Vehicle;
use App\Services\GenerateExcelService;
use App\Services\Financial\FinancialService;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
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

    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'description' => 'required',
            'amount' => 'required',
            'type' => 'required',
            'status_id' => 'required',
            'reference_id' => 'required',
            'table_reference_id' => 'required',
            'cost_center_id' => 'required',
        ]);

        if ($request->status_id == StatusEnum::DISCOUNT || $request->status_id == StatusEnum::RECEIVE){
            $request->validate(['finished_data' => 'required']);
        }

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;
        $arrayData['credential_id'] = $request->user()->id;

        unset($arrayData['proof_of_payment'], $arrayData['status_proof_of_payment'], $arrayData['observation_proof_of_payment']);

        $paymentData['proof_of_payment'] = $request->proof_of_payment ?? null;
        $paymentData['status_proof_of_payment'] = $request->status_proof_of_payment ?? null;
        $paymentData['observation_proof_of_payment'] = $request->observation_proof_of_payment ?? null;

        if ($request->table_reference_id == TableReferenceFinanceEnum::MAINTENANCE){
            $vehicle = Vehicle::find($request->reference_id);
            if (!$vehicle) return ResponseService::businessError('Id da referência não pertence a um veículo.');
            $arrayData['vehicle_id'] = $vehicle->id;
        }

        return $this->financialAccountsRepository->create($arrayData, $paymentData);
    }

    public function list(Request $request): \Illuminate\Http\JsonResponse
    {
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        $valid = false;
        foreach ($whereCriterious as $criterious){
            if(str_contains($criterious['field'], 'type')) $valid = true;
        }

        if ( $valid == false) return ResponseService::businessError('È obrigatorio usar a filtragem por tipo.');

        return response()->json($this->financialAccountsRepository->findAll($selectConfig, $whereCriterious, $request->user()));
    }

    public function listByDate(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'startDate' => 'required',
            'endDate' => 'required',
        ]);

        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        $valid = false;
        foreach ($whereCriterious as $criterious){
            if(str_contains($criterious['field'], 'type')) $valid = true;
        }

        if ( $valid == false) return ResponseService::businessError('È obrigatorio usar a filtragem por tipo.');

        return response()->json($this->financialAccountsRepository->findAllByDate($selectConfig, $whereCriterious, $request->startDate, $request->endDate));
    }

    public function download(Request $request): \Illuminate\Http\JsonResponse
    {
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        $valid = false;
        foreach ($whereCriterious as $criterious){
            if(str_contains($criterious['field'], 'type')) $valid = true;
        }

        if ( $valid == false) return ResponseService::businessError('È obrigatorio usar a filtragem por tipo.');

        $response = $this->financialAccountsRepository->findAllDownload($selectConfig, $whereCriterious);

        $header = [
            'DESCRICAO', 'VALOR', 'DATA_CADASTRO', 'DATA_PAGAMENTO', 'TIPO', 'STATUS', 'ID_REFERENCIA_DA_DESPESA',
            'REFERENCIA_DA_DESPESA', 'CPF_USUARIO', 'NOME_USUARIO', "NOME_COMPANIA", "TIME", "CENTRO_DE_CUSTO",
            "DATA_DA_APANHA", 'APANHA_ATIVA', 'ID_UNIDADE_APANHA', "NOME_UNIDADE_APANHA", "CODIGO"
        ];

        $filePath = GenerateExcelService::csv($header, $response['data']);

        return response()->json(['url' => $filePath]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'description' => 'required',
            'amount' => 'required',
            'due_date' => 'required',
            'type' => 'required',
            'status_id' => 'required',
            'financial_accounts_id' => 'required',
            'table_reference_id' => 'required',
            'reference_id' => 'required',
        ]);

        $arrayData = $request->all();
        $arrayData['credential_id'] = $request->user()->id;

        unset($arrayData['proof_of_payment'], $arrayData['status_proof_of_payment'], $arrayData['observation_proof_of_payment']);

        $paymentData['proof_of_payment'] = $request->proof_of_payment ?? null;
        $paymentData['status_proof_of_payment'] = $request->status_proof_of_payment ?? null;
        $paymentData['observation_proof_of_payment'] = $request->observation_proof_of_payment ?? null;

        return $this->financialAccountsRepository->update($request->financial_accounts_id, $arrayData, $paymentData);
    }

    public function enable(Request $request){
        $request->validate([
            'financial_accounts_id' => 'required',
            'enabled' => 'required'
        ]);

        return $this->financialAccountsRepository->enable($request->financial_accounts_id, $request->enabled);
    }

    public function analytic(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'start_date' => 'required',
            'end_date' => 'required'
        ]);

        return FinancialService::analytics($request->all(), $request->user());
    }

    public function generalReport(Request $request): \Illuminate\Http\JsonResponse
    {
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->financialAccountsRepository->generalReport($selectConfig, $whereCriterious));
    }

    public function catchRanking(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required',
            'end_date' => 'required'
        ]);

        return FinancialService::catchRanking($request->all(), $request->user());
    }
}
