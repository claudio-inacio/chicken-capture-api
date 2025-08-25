<?php

namespace App\Services\Main;

use App\Enum\Campain\MessageServiceEnum;
use App\Helpers\FormatHelper;
use App\Models\Utils\MessageConfig;
use App\Services\ResponseService;
use App\Services\Utils\ZApi\ZApiConnection;
use Illuminate\Http\JsonResponse;

class ZApiService
{
    public static function sendMessage($text, $phoneNumer): JsonResponse
    {
        try {
            $phoneNumer = FormatHelper::removeSpecialCaracterTel($phoneNumer);
            $messageConfig = MessageConfig::where('message_service_id', MessageServiceEnum::ZAPI)
                ->first();
            $response = ZApiConnection::sendText($messageConfig, $phoneNumer, $text, 1, 1);
            if ($response->status() == 200) {
                $body = json_decode($response->getBody(), true) ?? $response->body();
                LogService::save('ERROR_ZAPI_SEND_MESSAGE', $body);
                return ResponseService::businessError('Erro no envio de mensagem via whatsapp');
            }

            return ResponseService::success204();
        } catch (\Exception $exception) {
            return ResponseService::businessError('Erro no envio de mensagem via whatsapp', [
                'error' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile()
            ]);
        }
    }
}
