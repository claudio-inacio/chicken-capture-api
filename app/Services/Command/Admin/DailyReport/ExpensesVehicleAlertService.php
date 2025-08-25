<?php

namespace App\Services\Command\Admin\DailyReport;

use App\Interfaces\Admin\DailyReportInterface;
use App\Repositories\Vehicles\VehiclesRepository;
use JetBrains\PhpStorm\ArrayShape;

class ExpensesVehicleAlertService implements DailyReportInterface
{
    #[ArrayShape(['financial_expenses' => "\Illuminate\Support\Collection",
        'fuel_supplies' => "\Illuminate\Support\Collection", 'totals' => "array"])]
    public function getData(): array
    {
        //filtrar somente dia anterior ao de hoje
        $yesterdayStart = now()->subDay()->startOfDay(); // 00:00:00
        $yesterdayEnd   = now()->subDay()->endOfDay();   // 23:59:59

        $whereCriterious[] = [
            'field' => 'financial_accounts.created_at',
            'command' => 'between',
            'value' => [$yesterdayStart, $yesterdayEnd],
        ];

        $whereCriterious[] = [
            'field' => 'fuel_supply.created_at',
            'command' => 'between',
            'value' => [$yesterdayStart, $yesterdayEnd],
        ];

        $vehicleRepository = new VehiclesRepository();
        return $vehicleRepository->expenses([], $whereCriterious);
    }

    public function sendAlert()
    {
        //criar logica individual para enviar alert no futuro chamando apenas esse metodo.
    }
}
