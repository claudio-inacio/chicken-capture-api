<?php
namespace App\Services\Command\Job;

use App\Enum\Campain\MessageServiceEnum;
use App\Exceptions\GeniusRunTimeException;
use App\Helpers\FormatHelper;
use App\Models\Credential;
use App\Models\Utils\MessageConfig;
use App\Services\Main\LogService;
use App\Services\Utils\ZApi\ZApiConnection;
use Exception;

class SendGenericMessageJobService
{

    /**
     * @throws Exception
     */
    public static function run(string $message , Credential $credential): bool
    {
        try {
            $phoneNumber = FormatHelper::removeSpecialCaracterTel($credential->phone_number);

            //deixei pegando direto da compania 0, mais caso queria pegar do ususario logado só pegar do $credentialSystem
            $messageConfig = MessageConfig::where('message_service_id', MessageServiceEnum::ZAPI)
                ->first();

            $response = ZApiConnection::sendText($messageConfig, $phoneNumber, $message,  1, 1);
            $status = $response->status();


            if ($status != 200 and $status != 201){
                $responseBody = json_decode($response->getBody() ?? $response->body(), true);
                LogService::save('ErrorInSendMessage', [
                    'body' => $responseBody
                ]);

                throw new GeniusRunTimeException('Falha no envio de mensagem.', $responseBody);
            }

            return true;
        }catch (\Exception $exception) {
            LogService::save('ErrorInSendMessageCatch', [
                'error' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile()
            ]);

            throw new GeniusRunTimeException('Erro desconhecido.', [
                'error' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile()
            ]);
        }
    }
}
