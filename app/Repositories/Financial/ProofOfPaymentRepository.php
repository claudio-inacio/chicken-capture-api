<?php

namespace App\Repositories\Financial;

use App\Enum\Financial\ProofOfPaymentStatusEnum;
use App\Interfaces\Financial\ProofOfPaymentRepositoryInterface;
use App\Models\Financial\FinancialAccounts;
use App\Services\ResponseService;
use App\Services\Upload\UploadBase64Service;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;

class ProofOfPaymentRepository implements ProofOfPaymentRepositoryInterface
{
    /**
     * @throws Exception
     */
    #[ArrayShape(['data' => "mixed", 'total' => "int"])]
    public function selectByFinancial(int $financialId): array
    {
        $query = DB::table('financial.proof_of_payment')
            //->join('financial.financial_accounts', 'financial_accounts.id', '=', 'proof_of_payment.financial_id')
            ->where('proof_of_payment.financial_id', $financialId);

        $total = $query->count('proof_of_payment.id');

        $query->select([
            'proof_of_payment.*'
        ]);

        $result = $query->get()->toArray();

        foreach ($result as $item){
            if ($item->status_id == ProofOfPaymentStatusEnum::PENDENT) $item->status = 'PENDENTE';
            if ($item->status_id == ProofOfPaymentStatusEnum::APPROVED) $item->status = 'APROVADO';
            if ($item->status_id == ProofOfPaymentStatusEnum::REJECTED) $item->status = 'REJEITADO';
        }

        return [
            'data' => $result,
            'total' => $total
        ];
    }

    public function create(array $arrayData): JsonResponse
    {
        try {
            $financialAccounts = FinancialAccounts::find($arrayData['financial_id']);

            $upload = UploadBase64Service::uploadProofPayment($arrayData, $arrayData['credential_id'], $financialAccounts);

            if (!$upload['success']) {
                return ResponseService::businessError($upload['message'], $upload['error']);
            }

            return ResponseService::success204();
        } catch (Exception $e) {
            return ResponseService::internalServerError('Falha em registrar comprovante de pagamento', $e->getMessage());
        }
    }
}
