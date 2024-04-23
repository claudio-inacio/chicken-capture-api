<?php

namespace App\Services\Vehicles;

use App\Helpers\FormatHelper;
use App\Models\Financial\FinancialAccounts;
use App\Models\Vehicles\Vehicle;
use Illuminate\Support\Facades\DB;

class DriverAreaService
{
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

    public static function saveMileageVehicle(array $arrayRequest): array
    {
        DB::beginTransaction();
        try {
            $vehicle = Vehicle::whereId($arrayRequest['vehicle_id'])->first();

            if (!$vehicle)
                return [
                    'success' => false,
                    'message' => 'Falha em encontrar veiculo',
                    'error' => 'id do veiculo nao encontrado id -> '.$arrayRequest['vehicle_id']
                ];

            Vehicle::whereId($vehicle->id)->update([
                'mileage' => $arrayRequest['daily_end_km'],
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
