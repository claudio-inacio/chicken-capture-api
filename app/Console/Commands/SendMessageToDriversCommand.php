<?php

namespace App\Console\Commands;

use App\Services\Command\Proposal\SendMessageToDriversService;
use App\Services\Main\LogService;
use Illuminate\Console\Command;

class SendMessageToDriversCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:message_drivers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando para enviar mensagem para motoristas que nao inciaram seu dia';

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function handle(): int
    {
        try {
            SendMessageToDriversService::sendMessage();
            return parent::SUCCESS;
        } catch (\Throwable $exception){
            LogService::save(
                "ErrorSendMessageToDrivers",
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
