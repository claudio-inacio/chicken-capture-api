<?php

namespace App\Repositories\Main;

use App\Enum\Authentication\AccessGroupEnum;
use App\Enum\Financial\CostCenterIdEnum;
use App\Enum\Financial\ProofOfPaymentStatusEnum;
use App\Enum\Financial\StatusEnum;
use App\Enum\Financial\TableReferenceFinanceEnum;
use App\Enum\Financial\TypeFinanceEnum;
use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Main\DiaristRepositoryInterface;
use App\Models\Credential;
use App\Models\Financial\FinancialAccounts;
use App\Models\Main\Diarist;
use App\Models\Main\DiaristGroup;
use App\Services\ResponseService;
use App\Services\Upload\UploadBase64Service;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DiaristRepository implements DiaristRepositoryInterface
{
    public function getAll()
    {
        return Diarist::all();
    }

    public function findAll($selectConfig, array $whereCriterious, $credential): array
    {
        $query = DB::table('main.diarist')
            ->join('main.diarist_group', 'diarist_group.id', '=', 'diarist.diarist_group_id')
            ->leftJoin('main.team', 'team.id', '=', 'diarist.team_id')
            ->join('main.company', 'company.id', '=', 'diarist.company_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

// if ($credential->access_group_id != AccessGroupEnum::DEVELOPER && $credential->access_group_id != AccessGroupEnum::ADMINISTRATIVE) {
//     $query->where('diarist.company_id', $credential->company_id);
// }

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);

        $query->where('diarist.enabled', true)
            ->select([
                'diarist.*',
                'team.name as team_name',
                'company.name as company_name',
                'diarist_group.function_name',
                'diarist_group.daily'
            ]);

        $result = $query->get()->toArray();

        $arrayReturn = collect();
        $arrayDiaristType = collect();

        $diaristGroups = DiaristGroup::whereIn('id', collect($result)->pluck('diarist_group_id'))->get()->keyBy('id');
        foreach ($result as $item) {
            if (!$arrayReturn->has($item->phone_number)) {
                $item->total_daily = 1;
                $arrayReturn->put($item->phone_number, $item);
            } else {
                $arrayReturn[$item->phone_number]->daily += $item->daily;
                $arrayReturn[$item->phone_number]->total_daily += 1;
            }

            $functionName = $diaristGroups[$item->diarist_group_id]->function_name;
            $arrayDiaristType[$functionName] = ($arrayDiaristType[$functionName] ?? 0) + $item->daily;
        }

        //Formatar valores de decimal para Moeda REAL
        $arrayReturn->transform(function ($item) {
            if (is_numeric($item->daily)) {
                $item->daily = FormatHelper::decimalToBr($item->daily);
            }
            return $item;
        });

        //Formatar valores de decimal para Moeda REAL
        $arrayDiaristType->transform(function ($diaristValue) {
            return "R$ " . FormatHelper::decimalToBr($diaristValue);
        });

        return [
            'data' => $arrayReturn->values()->toArray(),
            'total' => $arrayReturn->count(),
            'total_values' => $arrayDiaristType->toArray(),
        ];
    }

    /**
     * @throws Exception
     */
    public function select(array $arrayData): array
    {
        $query = DB::table('main.diarist')
            ->join('main.diarist_group', 'diarist_group.id', '=', 'diarist.diarist_group_id')
            ->leftJoin('main.team', 'team.id', '=', 'diarist.team_id')
            ->join('main.company', 'company.id', '=', 'diarist.company_id')
            ->join('financial.financial_accounts', 'financial_accounts.reference_id', '=', 'diarist.id')
            ->leftJoin('financial.proof_of_payment', 'proof_of_payment.financial_id', '=', 'financial_accounts.id')
            ->where('financial_accounts.table_reference_id', TableReferenceFinanceEnum::DIARIST)
            ->whereBetween('date', [$arrayData['start_date'], $arrayData['end_date']])
            ->where(function ($query) use ($arrayData) {
                if ($arrayData['document'] != null) {
                    $query->orWhere('document', $arrayData['document']);
                }
                if ($arrayData['phone_number'] != null) {
                    $query->orWhere('phone_number', $arrayData['phone_number']);
                }
            });

        $total = $query->count('diarist.id');

        $query->select([
            'diarist.*',
            'company.name as company_name',
            'team.name as team_name',
            'financial_accounts.description as description_account',
            'financial_accounts.due_date as due_date_account',
            'financial_accounts.finished_data as finished_date_account',
            'financial_accounts.status_id as status',
            'financial_accounts.credential_id as credential_payment_id',
            'proof_of_payment.file_patch as proof_of_payment_url',
            'proof_of_payment.status_id as proof_of_payment_status_id',
            'diarist_group.function_name as diarist_group_function_name', 'diarist_group.daily as diarist_group_daily'
        ]);

        $result = $query->get()->toArray();
        $financialAccount = FinancialAccounts::where('table_reference_id', TableReferenceFinanceEnum::DIARIST)->get();
        $totalValue = 0;

        foreach ($result as $item) {
            if ($item->daily < 1) {
                $item->daily = $item->diarist_group_daily;
            }

            if ($item->proof_of_payment_status_id == ProofOfPaymentStatusEnum::PENDENT)
                $item->proof_of_payment_status_id = 'PENDENTE';
            if ($item->proof_of_payment_status_id == ProofOfPaymentStatusEnum::APPROVED)
                $item->proof_of_payment_status_id = 'APROVADO';
            if ($item->proof_of_payment_status_id == ProofOfPaymentStatusEnum::REJECTED)
                $item->proof_of_payment_status_id = 'REJEITADO';

            $item->due_date_account = FormatHelper::dateToBr($item->due_date_account);

            $item->finished_date_account ?
                $item->finished_date_account = FormatHelper::dateToBr($item->finished_date_account) : $item->finished_date_account = null;

            if ($item->status == StatusEnum::TO_DISCOUNT) $item->status = 'A PAGAR';
            if ($item->status == StatusEnum::DISCOUNT) $item->status = 'PAGO';
            if ($item->status == StatusEnum::DEFEATED) $item->status = 'CANCELADO';

            unset($item->diarist_group_daily);
            $totalValue = $totalValue + $item->daily;
            $item->daily = FormatHelper::decimalToBr($item->daily);

            foreach ($financialAccount as $account) {
                if ($account->reference_id == $item->id) {
                    $item->status_id = $account->status_id;
                }
            }
        }

        return [
            'data' => $result,
            'total' => $total,
            'total_value' => "R$ " . FormatHelper::decimalToBr($totalValue)
        ];
    }

    public function create(array $arrayData, $credential): JsonResponse
    {
        DB::beginTransaction();
        try {
            $diarist = Diarist::where('enabled', true)
                ->where('company_id', $arrayData['company_id'])
                ->whereDay('date', date('d', strtotime($arrayData['date'])))
                ->where(function ($query) use ($arrayData) {
                    if ($arrayData['document'] != null)
                        $query->where('document', $arrayData['document']);
                    if ($arrayData['phone_number'] != null)
                        $query->where('phone_number', $arrayData['phone_number']);
                })
                ->first();

            if ($diarist) {
                $day = (new \DateTime($arrayData['date']))->format('d-m-Y');
                return ResponseService::businessError('Esse diarista ja foi cadastrada para o dia -> ' . $day);
            }

            $diarist = Diarist::create($arrayData);

            if ($arrayData['daily'] == 0) {
                $diaristGroup = DiaristGroup::find($arrayData['diarist_group_id']);
                $arrayData['daily'] = $diaristGroup->daily;
            } else {
                $diaristGroup = new \stdClass();
                $diaristGroup->function_name = 'NAO_CONTEM';
            }

            $statusId = StatusEnum::TO_DISCOUNT;
            $finishedDate = null;
            if ($arrayData['paid'] == 'sim'){
                $statusId = StatusEnum::DISCOUNT;
                $finishedDate = now();
            }

            $document = $diarist->document ?? 'NAO CONTEM!';
            $phoneNumber = $diarist->phone_number ?? 'NAO CONTEM!';
            $financialAccount = FinancialAccounts::create([
                'description' => "Cadastro de diarista.",
                'cost_center_id' => CostCenterIdEnum::DIARIA,
                'description_data' => json_encode([
                    'name' => $diarist->name,
                    'document' => $document,
                    'phone_number' => $phoneNumber,
                    'function' => $diaristGroup->function_name,
                    'date' => $diarist->date
                ]),
                'amount' => $arrayData['daily'],
                'due_date' => (new \DateTime($arrayData['date']))->format('Y-m-d') . " 20:00:00",
                'type' => TypeFinanceEnum::TO_DISCOUNT,
                'credential_id' => $credential->id,
                'company_id' => $credential->company_id,
                'reference_id' => $diarist->id,
                'table_reference_id' => TableReferenceFinanceEnum::DIARIST,
                'status_id' => $statusId,
                'finished_data' => $finishedDate
            ]);

            if ($arrayData['paid'] == 'sim') {
                $paymentData['proof_of_payment'] = $arrayData['proof_of_payment'];
                $paymentData['status_proof_of_payment'] = $arrayData['status_proof_of_payment'];
                $paymentData['observation_proof_of_payment'] = $arrayData['observation_proof_of_payment'] ?? null;

                $upload = UploadBase64Service::uploadProofPayment($paymentData, $arrayData['credential_id'], $financialAccount);
                if (!$upload['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($upload['message'], $upload['error']);
                }
            }

            DB::commit();
            return ResponseService::success204();
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseService::internalServerError('Falha em registrar diarista', $e->getMessage());
        }
    }

    public function update(int $id, array $arrayData, $credential): JsonResponse
    {
        unset($arrayData['diarist_id']);
        try {
            DB::beginTransaction();
            $diarist = Diarist::where('id', '<>', $id)
                ->where('enabled', true)
                ->where('company_id', $arrayData['company_id'])
                ->whereDay('date', date('d', strtotime($arrayData['date'])))
                ->where(function ($query) use ($arrayData) {
                    if ($arrayData['document'] != null)
                        $query->where('document', $arrayData['document']);
                    if ($arrayData['phone_number'] != null)
                        $query->where('phone_number', $arrayData['phone_number']);
                })
                ->first();

            if ($diarist) {
                $day = (new \DateTime($arrayData['date']))->format('d-m-Y');
                DB::rollBack();
                return ResponseService::businessError('Esse diarista ja foi cadastrada para o dia -> ' . $day);
            }

            Diarist::whereId($id)->update($arrayData);

            if ($arrayData['daily'] == 0) {
                $diaristGroup = DiaristGroup::find($arrayData['diarist_group_id']);
                $arrayData['daily'] = $diaristGroup->daily;
            } else {
                $diaristGroup = new \stdClass();
                $diaristGroup->function_name = 'NAO_CONTEM';
            }

            $document = $diarist->document ?? 'NAO CONTEM!';
            $phoneNumber = $diarist->phone_number ?? 'NAO CONTEM!';

            $statusId = StatusEnum::TO_DISCOUNT;
            $finishedDate = null;
            if ($arrayData['paid'] == 'sim'){
                $statusId = StatusEnum::DISCOUNT;
                $finishedDate = now();
            }

            $financialAccount = FinancialAccounts::where('reference_id', $diarist->id)->update([
                'description' => "Cadastro de diarista.",
                'cost_center_id' => CostCenterIdEnum::DIARIA,
                'description_data' => json_encode([
                    'name' => $diarist->name,
                    'document' => $document,
                    'phone_number' => $phoneNumber,
                    'function' => $diaristGroup->function_name,
                    'date' => $diarist->date
                ]),
                'amount' => $arrayData['daily'],
                'due_date' => (new \DateTime($arrayData['date']))->format('Y-m-d') . " 20:00:00",
                'type' => TypeFinanceEnum::TO_DISCOUNT,
                'credential_id' => $credential->id,
                'company_id' => $credential->company_id,
                'table_reference_id' => TableReferenceFinanceEnum::DIARIST,
                'status_id' => $statusId,
                'finished_data' => $finishedDate
            ]);

            if ($arrayData['paid'] == 'sim') {
                $paymentData['proof_of_payment'] = $arrayData['proof_of_payment'];
                $paymentData['status_proof_of_payment'] = $arrayData['status_proof_of_payment'];
                $paymentData['observation_proof_of_payment'] = $arrayData['observation_proof_of_payment'] ?? null;

                $upload = UploadBase64Service::uploadProofPayment($paymentData, $arrayData['credential_id'], $financialAccount);
                if (!$upload['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($upload['message'], $upload['error']);
                }
            }

            DB::commit();
            return ResponseService::success204();
        } catch (Exception $e) {
            return ResponseService::internalServerError('Falha em  registrar diarista', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): JsonResponse
    {
        try {
            Diarist::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (Exception $e) {
            return ResponseService::internalServerError('Falha Ativar/Desativar diarista', $e->getMessage());
        }
    }
}
