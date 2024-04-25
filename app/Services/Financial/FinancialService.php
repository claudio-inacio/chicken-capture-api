<?php

namespace App\Services\Financial;

use App\Enum\Financial\TypeFinanceEnum;
use App\Helpers\FormatHelper;
use App\Models\Financial\FinancialAccounts;
use App\Models\Vehicles\Vehicle;
use Illuminate\Support\Facades\DB;

class FinancialService
{
    public static function postAccountReceivable(float $value, int $credentialId, int $companyId, int $referenceId, int $tableId): array
    {
        try {
            $days = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
            $day = '0';

            if(date('d') > 0 and date('d') < 16) $day = '15';
            if(date('d') > 15 and date('d') < $days) $day = $days;

            FinancialAccounts::create([
                'description' => 'Apanha Diária',
                'amount' => $value,
                'due_date' => date('Y-m').'-'.$day,
                'reference_id' => $referenceId,
                'table_reference_id' => $tableId,
                'type' => TypeFinanceEnum::TO_RECEIVE,
                'credential_id' => $credentialId,
                'company_id' => $companyId,
            ]);

            return [
                'success' => true
            ];
        }catch (\Exception $e){
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
            if(date('d') > 0 and date('d') < 16) $day = '15';
            if(date('d') > 15 and date('d') < $days) $day = $days;

            FinancialAccounts::where('reference_id', $referenceId)->update([
                'amount' => $value,
                'due_date' => date('Y-m').'-'.$day,
                'type' => TypeFinanceEnum::TO_RECEIVE,
                'credential_id' => $credentialId,
                'company_id' => $companyId,
            ]);

            return [
                'success' => true
            ];
        }catch (\Exception $e){
            return [
                'success' => true,
                'message' => 'falha em registrar finança de apanha diaria',
                'error' => $e->getMessage()
            ];
        }
    }

    public static function saveMaintenanceFinance(array $arrayRequest): array
    {
        DB::beginTransaction();
        try {
            $financialAccount = FinancialAccounts::where('description', 'Despesas com manuntenca')
                ->where('credential_id', $arrayRequest['credential_id'])
                ->where('company_id', $arrayRequest['company_id'])
                ->whereDay('created_at', date('d'))
                ->first();

            if ($financialAccount){
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
                'amount' => FormatHelper::brlTodecimal($arrayRequest['maintenance_expenses']),
                'due_date' => now(),
                'credential_id' => $arrayRequest['credential_id'],
                'company_id' => $arrayRequest['company_id'],
            ]);
            DB::commit();
            return [
                'success' => true
            ];
        } catch (\Exception $exception){
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Falha em cadastrar finanças',
                'error' => $exception->getMessage()
            ];
        }
    }

    public static function saveFuelFinance(array $arrayRequest): array
    {
        DB::beginTransaction();
        try {
            $financialAccount = FinancialAccounts::where('description', 'Despesas com combustivel')
                ->where('credential_id', $arrayRequest['credential_id'])
                ->where('company_id', $arrayRequest['company_id'])
                ->whereDay('created_at', date('d'))
                ->first();

            if ($financialAccount){
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
                'amount' => FormatHelper::brlTodecimal($arrayRequest['total_supply_value']),
                'due_date' => now(),
                'credential_id' => $arrayRequest['credential_id'],
                'company_id' => $arrayRequest['company_id'],
            ]);
            DB::commit();
            return [
                'success' => true
            ];
        } catch (\Exception $exception){
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Falha em cadastrar finanças',
                'error' => $exception->getMessage()
            ];
        }
    }
}
