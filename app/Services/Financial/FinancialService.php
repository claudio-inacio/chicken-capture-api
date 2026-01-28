<?php

namespace App\Services\Financial;

use App\Enum\Financial\CostCenterIdEnum;
use App\Enum\Financial\ProofOfPaymentStatusEnum;
use App\Enum\Financial\StatusEnum;
use App\Enum\Financial\TableReferenceFinanceEnum;
use App\Enum\Financial\TypeFinanceEnum;
use App\Helpers\FormatHelper;
use App\Models\Financial\FinancialAccounts;
use App\Models\Main\Team;
use App\Services\ResponseService;
use App\Services\Upload\UploadBase64Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialService
{
    public static function postAccountReceivable(float $value, int $credentialId, int $companyId, int $referenceId, int $tableId, int $teamId): array
    {
        try {
            $days = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
            $day = '0';

            if (date('d') > 0 and date('d') < 16) $day = '15';
            if (date('d') > 15 and date('d') < $days) $day = $days;

            FinancialAccounts::create([
                'description' => 'Apanha Diária',
                'cost_center_id' => CostCenterIdEnum::APANHAS,
                'amount' => $value,
                'due_date' => date('Y-m') . '-' . $day,
                'reference_id' => $referenceId,
                'status_id' => StatusEnum::TO_RECEIVE,
                'table_reference_id' => $tableId,
                'type' => TypeFinanceEnum::TO_RECEIVE,
                'credential_id' => $credentialId,
                'company_id' => $companyId,
                'team_id' => $teamId
            ]);

            return [
                'success' => true
            ];
        } catch (\Exception $e) {
            return [
                'success' => true,
                'message' => 'falha em registrar finança de apanha diaria',
                'error' => $e->getMessage()
            ];
        }
    }

    public static function updateAccountReceivale(float $value, int $credentialId, int $companyId, int $referenceId): array
    {
        try {
            $days = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
            $day = '0';
            if (date('d') > 0 and date('d') < 16) $day = '15';
            if (date('d') > 15 and date('d') < $days) $day = $days;

            FinancialAccounts::where('reference_id', $referenceId)->update([
                'amount' => $value,
                'due_date' => date('Y-m') . '-' . $day,
                'type' => TypeFinanceEnum::TO_RECEIVE,
                'status_id' => StatusEnum::TO_RECEIVE,
                'credential_id' => $credentialId,
                'company_id' => $companyId,
            ]);

            return [
                'success' => true
            ];
        } catch (\Exception $e) {
            return [
                'success' => true,
                'message' => 'falha em registrar finança de apanha diaria',
                'error' => $e->getMessage()
            ];
        }
    }

    public static function saveMaintenanceFinance(array $arrayRequest, int $referenceId, Team $team, $proofPayment = null): array
    {
        DB::beginTransaction();
        try {
            $financialAccount = FinancialAccounts::where('description', 'Despesas com manuntenca')
                ->where('reference_id', $referenceId)
                ->where('credential_id', $arrayRequest['credential_id'])
                ->where('company_id', $arrayRequest['company_id'])
                ->whereDay('created_at', date('d'))
                ->first();

            if ($financialAccount) {
                FinancialAccounts::whereId($financialAccount->id)->update([
                    'amount' => $financialAccount->amount + FormatHelper::brlTodecimal($arrayRequest['maintenance_expenses']),
                ]);

                DB::commit();
                return [
                    'success' => true
                ];
            }

            $financialAccount = FinancialAccounts::create([
                'description' => 'Despesas com manuntencao',
                'cost_center_id' => CostCenterIdEnum::VEICULO,
                'amount' => FormatHelper::brlTodecimal($arrayRequest['maintenance_expenses']),
                'type' => TypeFinanceEnum::TO_DISCOUNT,
                'status_id' => StatusEnum::TO_DISCOUNT,
                'reference_id' => $referenceId,
                'table_reference_id' => TableReferenceFinanceEnum::VEHICLE,
                'due_date' => now(),
                'vehicle_id' => $arrayRequest['vehicle_id'],
                'credential_id' => $arrayRequest['credential_id'],
                'company_id' => $arrayRequest['company_id'],
                'team_id' => $team->id
            ]);

            if ($proofPayment) {
                $arrayData = [
                    'proof_of_payment' => $proofPayment,
                    'status_proof_of_payment' => ProofOfPaymentStatusEnum::PENDENT,
                    'observation_proof_of_payment' => 'Despesa de veiculo',
                ];

                $upload = UploadBase64Service::uploadProofPayment($arrayData, $arrayRequest['credential_id'], $financialAccount);

                if (!$upload['success']) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => 'Falha em cadastrar comprovante de pagamento',
                        'error' => null
                    ];
                }
            }

            DB::commit();
            return [
                'success' => true
            ];
        } catch (\Exception $exception) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Falha em cadastrar finanças',
                'error' => [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine()
                ]
            ];
        }
    }

    public static function saveFuelFinance(array $arrayRequest, int $referenceId, Team $team, $proofPayment = null): array
    {
        DB::beginTransaction();
        try {
            $financialAccount = FinancialAccounts::where('description', 'Despesas com combustivel')
                ->where('reference_id', $referenceId)
                ->where('credential_id', $arrayRequest['credential_id'])
                ->where('company_id', $arrayRequest['company_id'])
                ->whereDay('created_at', date('d'))
                ->first();

            if ($financialAccount) {
                FinancialAccounts::whereId($financialAccount->id)->update([
                    'amount' => $financialAccount->amount + FormatHelper::brlTodecimal($arrayRequest['total_supply_value']),
                ]);

                DB::commit();
                return [
                    'success' => true
                ];
            }

            $financialAccount = FinancialAccounts::create([
                'description' => 'Despesas com combustivel',
                'cost_center_id' => CostCenterIdEnum::FUEL,
                'amount' => FormatHelper::brlTodecimal($arrayRequest['total_supply_value']),
                'type' => TypeFinanceEnum::TO_DISCOUNT,
                'status_id' => StatusEnum::TO_DISCOUNT,
                'reference_id' => $referenceId,
                'table_reference_id' => TableReferenceFinanceEnum::FUEL,
                'due_date' => now(),
                'vehicle_id' => $arrayRequest['vehicle_id'],
                'credential_id' => $arrayRequest['credential_id'],
                'company_id' => $arrayRequest['company_id'],
                'team_id' => $team->id
            ]);

            if ($proofPayment) {
                $arrayData = [
                    'proof_of_payment' => $proofPayment,
                    'status_proof_of_payment' => ProofOfPaymentStatusEnum::PENDENT,
                    'observation_proof_of_payment' => 'Despesa de combustivel do veiculo'
                ];

                $upload = UploadBase64Service::uploadProofPayment($arrayData, $arrayRequest['credential_id'], $financialAccount);

                if (!$upload['success']) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => 'Falha em cadastrar comprovante de pagamento',
                        'error' => null
                    ];
                }
            }

            DB::commit();
            return [
                'success' => true
            ];
        } catch (\Exception $exception) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Falha em cadastrar finanças',
                'error' => [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine()
                ]
            ];
        }
    }

    public static function analytics(array $arrayRequest, $user): JsonResponse
    {
        try {
            $startDate = FormatHelper::dateToUsTimeStamp($arrayRequest['start_date']);
            $endDate = FormatHelper::dateToUsTimeStamp($arrayRequest['end_date']);

            // Obter contas financeiras a receber
            $financialAccountsToReceive = FinancialAccounts::whereBetween('due_date', [$startDate, $endDate])
                ->where('type', TypeFinanceEnum::TO_RECEIVE)
                ->where('status_id', StatusEnum::TO_RECEIVE)
                ->where('enabled', true)
                ->where('finished_data', null)
                ->where('company_id', $user->company_id)
                ->get();

            $toReceiveValue = $financialAccountsToReceive->sum('amount');
            $toReceiveDescriptions = $financialAccountsToReceive->map(function ($item) {
                return [
                    'description' => $item->description,
                    'value' => FormatHelper::decimalToBr($item->amount),
                    'due_date' => $item->due_date,
                    'finished_data' => $item->finished_data,
                    'status_id' => $item->status_id,
                ];
            });

            // Obter contas financeiras recebidas
            $financialAccountsReceive = FinancialAccounts::whereBetween('due_date', [$startDate, $endDate])
                ->where('type', TypeFinanceEnum::TO_RECEIVE)
                ->where('status_id', StatusEnum::RECEIVE)
                ->where('enabled', true)
                ->where('finished_data', '<>', null)
                ->where('company_id', $user->company_id)
                ->get();

            $receiveValue = $financialAccountsReceive->sum('amount');
            $receiveDescriptions = $financialAccountsReceive->map(function ($item) {
                return [
                    'description' => $item->description,
                    'value' => FormatHelper::decimalToBr($item->amount),
                    'due_date' => $item->due_date,
                    'finished_data' => $item->finished_data,
                    'status_id' => $item->status_id,
                ];
            });

            // Obter contas financeiras a descontar
            $financialAccountsToDiscount = FinancialAccounts::whereBetween('due_date', [$startDate, $endDate])
                ->where('type', TypeFinanceEnum::TO_DISCOUNT)
                ->where('status_id', StatusEnum::TO_DISCOUNT)
                ->where('enabled', true)
                ->where('finished_data', null)
                ->where('company_id', $user->company_id)
                ->get();

            $toDiscountValue = $financialAccountsToDiscount->sum('amount');
            $toDiscountDescriptions = $financialAccountsToDiscount->map(function ($item) {
                return [
                    'description' => $item->description,
                    'value' => FormatHelper::decimalToBr($item->amount),
                    'due_date' => $item->due_date,
                    'finished_data' => $item->finished_data,
                    'status_id' => $item->status_id,
                ];
            });

            // Obter contas financeiras descontadas
            $financialAccountsDiscount = FinancialAccounts::whereBetween('due_date', [$startDate, $endDate])
                ->where('type', TypeFinanceEnum::TO_DISCOUNT)
                ->where('status_id', StatusEnum::DISCOUNT)
                ->where('enabled', true)
                ->where('finished_data', '<>', null)
                ->where('company_id', $user->company_id)
                ->get();

            $discountValue = $financialAccountsDiscount->sum('amount');
            $discountDescriptions = $financialAccountsDiscount->map(function ($item) {
                return [
                    'description' => $item->description,
                    'value' => FormatHelper::decimalToBr($item->amount),
                    'due_date' => $item->due_date,
                    'finished_data' => $item->finished_data,
                    'status_id' => $item->status_id,
                ];
            });

            $analyticFinancialAccounts = [
                'receive' => [
                    'details' => $receiveDescriptions,
                    'total' => FormatHelper::decimalToBr($receiveValue)
                ],
                'to_receive' => [
                    'details' => $toReceiveDescriptions,
                    'total' => FormatHelper::decimalToBr($toReceiveValue),
                ],
                'to_discount' => [
                    'details' => $toDiscountDescriptions,
                    'total' => FormatHelper::decimalToBr($toDiscountValue),
                ],
                'discount' => [
                    'details' => $discountDescriptions,
                    'total' => FormatHelper::decimalToBr($discountValue),
                ],
                'finance_income_to_receive' => FormatHelper::decimalToBr($toReceiveValue - $toDiscountValue),
                'finance_income_receive' => FormatHelper::decimalToBr($receiveValue - $discountValue)
            ];

            return ResponseService::success('Sucesso em listar analítico de finanças', $analyticFinancialAccounts);
        } catch (\Exception $e) {
            return ResponseService::internalServerError('Falha em listar analítico de finanças', $e->getMessage());
        }
    }

    public static function catchRanking(array $arrayRequest, $user): JsonResponse
    {
        try {
            $startDate = FormatHelper::dateToUsTimeStamp($arrayRequest['start_date']);
            $endDate   = FormatHelper::dateToUsTimeStamp($arrayRequest['end_date']);

            $ranking = FinancialAccounts::query()
                ->select([
                    'team.id as team_id',
                    'team.name as team_name',
                    'person.name as driver_name',
                    DB::raw('SUM(financial_accounts.amount) as total_amount'),
                    DB::raw('COUNT(financial_accounts.id) as total_registros')
                ])
                ->join('main.team', 'team.id', '=', 'financial_accounts.team_id')
                ->join('authentication.credential', 'credential.id', '=', 'team.driver_credential_id')
                ->join('authentication.person', 'person.id', '=', 'credential.person_id')
                ->where('financial_accounts.company_id', $user->company_id)
                ->whereBetween('financial_accounts.created_at', [$startDate, $endDate])
                ->whereIn('financial_accounts.cost_center_id', [
                    CostCenterIdEnum::APANHAS,
                    CostCenterIdEnum::TEAM_BONUS_AMOUNT
                ])
                ->groupBy('team.id', 'team.name', 'person.name')
                ->orderByDesc('total_amount')
                ->get();

            return ResponseService::success('sucesso em listar ranking de apanha', $ranking);
        } catch (\Exception $e) {
            return ResponseService::internalServerError('Falha em listar ranking de apanha', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile(),
            ]);
        }
    }

    public static function validCostCenter(int $costCenterId): array
    {
        if ($costCenterId == CostCenterIdEnum::TEAM_BONUS_AMOUNT){
            return ['team_id' => 'required'];
        }

        if ($costCenterId == CostCenterIdEnum::VEICULO){
            return ['vehicle_id' => 'required'];
        }

        if ($costCenterId == CostCenterIdEnum::MOTORISTA){
            return ['driver_credential_id' => 'required'];
        }

        return [];
    }
}
