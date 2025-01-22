<?php

namespace App\Http\Controllers\Api\Financial;

use App\Enum\Financial\StatusEnum;
use App\Http\Controllers\Controller;
use App\Interfaces\Financial\CostCenterRepositoryInterface;
use App\Interfaces\Financial\ProofOfPaymentRepositoryInterface;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProofOfPaymentController extends Controller
{
    private ProofOfPaymentRepositoryInterface $paymentRepository;

    public function __construct
    (
        ProofOfPaymentRepositoryInterface $paymentRepository
    )
    {
        $this->paymentRepository = $paymentRepository;
    }

    public function create(Request $request) {
        $request->validate([
            'proof_of_payment' => 'required',
            'status_proof_of_payment' => 'required',
            'financial_id' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['credential_id'] = $request->user()->id;

        return $this->paymentRepository->create($arrayData);
    }

    public function list(Request $request): JsonResponse
    {
        $request->validate([
            'financial_id' => 'required',
        ]);

        $consult = $this->paymentRepository->selectByFinancial($request->financial_id);

        return ResponseService::success('Consulta realizada com sucesso', $consult['data']);
    }
}
