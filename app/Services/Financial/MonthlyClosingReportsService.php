<?php

namespace App\Services\Financial;

use App\Helpers\FormatHelper;
use App\Models\Financial\MonthlyClosingReports;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class MonthlyClosingReportsService
{
    public static function createOrUpdate(array $arrayRequest): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();
        try {
            $monthly = MonthlyClosingReports::where('month', $arrayRequest['month'])
                ->where('year', $arrayRequest['year'])
                ->where('company_id', $arrayRequest['company_id'])
                ->first();

            if ($monthly){
                MonthlyClosingReports::whereId($monthly->id)->update([
                    'total_expenses' => $monthly->total_expenses + FormatHelper::brlTodecimal($arrayRequest['total_expenses']),
                    'total_income' => $monthly->total_income + FormatHelper::brlTodecimal($arrayRequest['total_income']),
                ]);
                DB::commit();
                return ResponseService::success('Registro atualizado com sucesso!');
            }

            $monthlyClosing = MonthlyClosingReports::create([
                'month' => $arrayRequest['month'],
                'year' => $arrayRequest['year'],
                'company_id' => $arrayRequest['company_id'],
                'total_expenses' => FormatHelper::brlTodecimal($arrayRequest['total_expenses']),
                'total_income' => FormatHelper::brlTodecimal($arrayRequest['total_income']),
            ]);
            DB::commit();
            return ResponseService::success('Fechamento mensal cadastrada com sucesso!', [
                'monthly_closing_reports' => $monthlyClosing->id
            ]);
        } catch (\Exception $exception){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em cadastrar mensalidade', $exception->getMessage());
        }
    }
}
