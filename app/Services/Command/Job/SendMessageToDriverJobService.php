<?php

namespace App\Services\Command\Job;

use App\Enum\Campain\MessageServiceEnum;
use App\Exceptions\GeniusRunTimeException;
use App\Helpers\FormatHelper;
use App\Models\Utils\MessageConfig;
use App\Services\Main\LogService;
use App\Services\Utils\ZApi\ZApiConnection;
use Exception;

class SendMessageToDriverJobService
{
    /**
     * @throws Exception
     */
    public static function run(object $driver): bool
    {
        try {
            $phoneNumer = FormatHelper::removeSpecialCaracterTel($driver->phone_number);
            $messageConfig = MessageConfig::where('message_service_id', MessageServiceEnum::ZAPI)
                ->first();

            $message = "Boa tarde {$driver->name}, você ainda não iniciou o dia no sistema.
Acesse agora e registre seu início de trabalho. https://443f6e4c047e.ngrok-free.app/sign-in";

            $response = ZApiConnection::sendText($messageConfig, $phoneNumer, $message, 1, 1);
            if ($response->status() == 200) {
                $body = json_decode($response->getBody(), true) ?? $response->body();
                LogService::save('ERROR_ZAPI_SEND_MESSAGE', $body);

                throw new GeniusRunTimeException('Erro no envio de mensagem via whatsapp');
            }

            return true;
        } catch (\Exception $exception) {
            LogService::save('ERROR_ZAPI_SEND_MESSAGE_EXCEPTION', [
                'error' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile()
            ]);

            throw new GeniusRunTimeException('Erro no envio de mensagem via whatsapp. COD 2');
        }
    }
}
