<?php

namespace App\Services\Financial;

use App\Enum\Financial\CostCenterIdEnum;
use App\Enum\Financial\StatusEnum;
use App\Enum\Financial\TableReferenceFinanceEnum;
use App\Enum\Financial\TypeFinanceEnum;
use App\Helpers\FormatHelper;
use App\Models\Financial\FinancialAccounts;
use App\Models\Main\Team;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
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

    public static function saveMaintenanceFinance(array $arrayRequest, int $referenceId, Team $team): array
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

            FinancialAccounts::create([
                'description' => 'Despesas com manuntencao',
                'cost_center_id' => CostCenterIdEnum::VEICULO,
                'amount' => FormatHelper::brlTodecimal($arrayRequest['maintenance_expenses']),
                'type' => TypeFinanceEnum::TO_DISCOUNT,
                'status_id' => StatusEnum::TO_DISCOUNT,
                'reference_id' => $referenceId,
                'table_reference_id' => TableReferenceFinanceEnum::MAINTENANCE,
                'due_date' => now(),
                'vehicle_id' => $arrayRequest['vehicle_id'],
                'credential_id' => $arrayRequest['credential_id'],
                'company_id' => $arrayRequest['company_id'],
                'team_id' => $team->id
            ]);
            DB::commit();
            return [
                'success' => true
            ];
        } catch (\Exception $exception) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Falha em cadastrar finanças',
                'error' => $exception->getMessage()
            ];
        }
    }

    public static function saveFuelFinance(array $arrayRequest, int $referenceId, Team $team): array
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

            FinancialAccounts::create([
                'description' => 'Despesas com combustivel',
                'cost_center_id' => CostCenterIdEnum::VEICULO,
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
            DB::commit();
            return [
                'success' => true
            ];
        } catch (\Exception $exception) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Falha em cadastrar finanças',
                'error' => $exception->getMessage()
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
}
