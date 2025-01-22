<?php
namespace App\Services\Upload;

use App\Models\Financial\FinancialAccounts;
use App\Models\Financial\ProofOfPayment;
use App\Services\Main\LogService;
use Illuminate\Support\Facades\DB;

class UploadBase64Service {
    public static function uploadProofPayment($arrayPayment, $credentialId, FinancialAccounts $financialAccounts): array
    {
        try {
            LogService::save('PayloadUpload', [
                'arrayPayment' => $arrayPayment,
                'credentialId' => $credentialId,
                'financialAccountId' => $financialAccounts->id
            ]);

            $dateNow = date("Y/m/d");

            if (!file_exists(storage_path() . "/app/public/$dateNow"))
                mkdir(storage_path() . "/app/public/$dateNow", 0777, true);
            if (!file_exists(storage_path() . "/app/public/$dateNow/$credentialId"))
                mkdir(storage_path() . "/app/public/$dateNow/$credentialId", 0777, true);


            $filePath = storage_path() . "/app/public/$dateNow/$credentialId";

            $base64Image = explode(";base64,", $arrayPayment['proof_of_payment']);

            $explodeImage = explode("image/", $base64Image[0]);
            $imageType = $explodeImage[1];
            $image_base64 = base64_decode($base64Image[1]);
            $name = uniqid() . '.' . $imageType;

            file_put_contents("$filePath/$name", $image_base64);

            $url = "$dateNow/$credentialId/$name";

            $arrayData['financial_id'] = $financialAccounts->id;
            $arrayData['file_patch'] = $url;
            $arrayData['file_type'] = $imageType;
            $arrayData['file_name'] = $name;
            $arrayData['status_id'] = $arrayPayment['status_proof_of_payment'];
            $arrayData['credential_id'] = $credentialId;
            $arrayData['observation'] = $arrayPayment['observation_proof_of_payment'] ?? null;

            try {
                LogService::save('ArrayDataProofOfPayment', [
                    'data' => $arrayData
                ]);
                $register = ProofOfPayment::create($arrayData);
            } catch (\Exception $exception){
                return [
                    'success' => false,
                    'message' => 'Falha em cadastrar comprovante de pagamento',
                    'error' => [
                        'message' => $exception->getMessage(),
                        'line' => $exception->getLine()
                    ]
                ];
            }

            if (!$register) {
                return [
                    'success' => false,
                    'message' => 'Falha em registrar dados do comprovante de pagamento!',
                    'error' => []
                ];
            }

            return [
                'success' => true
            ];
        }catch (\Exception $exception){
            return [
                'success' => false,
                'message' => 'Falha em cadastrar comprovante de pagamento',
                'error' => [
                    'message' => $exception->getMessage(),
                    'line' => $exception->getLine()
                ]
            ];
        }
    }
}
