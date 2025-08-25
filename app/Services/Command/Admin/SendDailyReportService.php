<?php

namespace App\Services\Command\Admin;

use App\Enum\Authentication\AccessGroupEnum;
use App\Helpers\FormatHelper;
use App\Jobs\Command\SendGenericMessageJob;
use App\Models\Credential;
use App\Services\Command\Admin\DailyReport\ExpensesVehicleAlertService;
use App\Services\Main\LogService;
use Exception;

class SendDailyReportService
{
    /**
     * @throws Exception
     */

    public function sendAlert(): bool|null
    {
        try {
            // NUNCA UTILIZOU O SISTEMA
            $vehicleRepository = new ExpensesVehicleAlertService();
            $vehicleExpense = $vehicleRepository->getData();
            $expense = FormatHelper::decimalToBr($vehicleExpense['totals']['expenses']);
            $fuel = FormatHelper::decimalToBr($vehicleExpense['totals']['fuel']);
            $total = FormatHelper::decimalToBr($vehicleExpense['totals']['general']);

            $credentials = Credential::where('access_group_id', AccessGroupEnum::ADMINISTRATIVE)->get();

            foreach ($credentials as $credential){
                $message = "*VT BISCOLA - Informa:*
Bom dia, $credential->name! Desejamos um excelente dia.
Aqui está o resumo de gasto do seus veículos ontem:

- Gastos com manunteção: $expense
- Gastos com combustivel: $fuel
- Gasto total: $total

Caso necessite adcionar mais informações no relatorio diário Solicite ao suporte (44988659669).";

                SendGenericMessageJob::dispatch($message, $credential)->delay(1)->onQueue('messages');
            }

            return true;
        }catch (\Exception $exception){
            LogService::save('ERROR_SEND_ALERT', [
                'error' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile()
            ]);

            return false;
        }

    }
}
