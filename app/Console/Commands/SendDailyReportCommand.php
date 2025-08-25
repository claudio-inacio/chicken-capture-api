<?php

namespace App\Console\Commands;

use App\Services\Command\Admin\SendDailyReportService;
use App\Services\Main\LogService;
use Illuminate\Console\Command;

class SendDailyReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:daily_report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando para relatorio de gastos do dia anterior';

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function handle(): int
    {
        try {
            $sendDailyReport = new SendDailyReportService();
            $sendDailyReport->sendAlert();
            return parent::SUCCESS;
        } catch (\Throwable $exception){
            LogService::save(
                "ErrorSendDailyReport",
                [
                    "line" => $exception->getLine(),
                    "getFile" => $exception->getFile(),
                    "getCode" => $exception->getCode(),
                    "message" => $exception->getMessage(),
                ]
            );
            return parent::FAILURE;
        }
    }
}
