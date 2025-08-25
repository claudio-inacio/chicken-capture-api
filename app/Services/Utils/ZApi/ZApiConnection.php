<?php

namespace App\Services\Utils\ZApi;

use App\Models\Utils\MessageConfig;
use App\Services\Main\LogService;
use Illuminate\Support\Facades\Http;

class ZApiConnection
{
    public static function sendText(
        MessageConfig $messageConfig,
                      $phone = '',
                      $message = '',
                      $delayTyping = 15,
                      $delayMessage = 10
    ): \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
    {
        $apiURL = "https://api.z-api.io/instances/{$messageConfig->external_id}/token/{$messageConfig->auth}" . '/send-text';

        // POST Data
        $postInput = [
            'phone' => $phone,
            'message' => $message,
            'delayTyping' => $delayTyping,
            'delayMessage' => $delayMessage
        ];
        // Headers
        $headers = [
            "content-type" => "application/json",
            "Client-Token" => $messageConfig->client_id
        ];

        LogService::save("ZApiMessageConfigService::sendText", [
            'apiUrl' => $apiURL,
            'postInput' => $postInput,
            'headers' => $headers,
            'messageConfig' => $messageConfig
        ]);

        $response = Http::withHeaders($headers)->post($apiURL, $postInput);
        LogService::save("Z-API_SEND_TEXT_RESPONSE", [
            'body' => $response->body(),
            'status' => $response->status()
        ]);

        return $response;
    }
}
