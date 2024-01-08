<?php

namespace App\Services;


class ResponseService
{
    public static function reponse(bool $success, string $message, $error, int $code){
        return [
            'success' => $success,
            'message' => $message ?? '',
            'error' => $error ?? '',
            'code' => $code ?? 200
        ];
    }

    public static function invalidArguments(string $message, $error){
        return [
            'success' => false,
            'message' => $message ?? '',
            'error' => '',
            'code' => 400
        ];
    }

    public static function unauthenticated(string $message, $error){
        return [
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? '',
            'code' => 401
        ];
    }

    public static function unauthorized(string $message, $error){
        return [
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? '',
            'code' => 403
        ];
    }

    public static function notAcceptable(string $message, $error){
        return [
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? '',
            'code' => 409
        ];
    }

    public static function timeout(string $message, $error){
        return [
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? '',
            'code' => 408
        ];
    }

    public static function conflict(string $message, $error){
        return [
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? '',
            'code' => 409
        ];
    }

    public static function businessError(string $message, $error){
        return [
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? '',
            'code' => 422
        ];
    }

    public static function internalServerError(string $message, $error){
        return [
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? '',
            'code' => 500
        ];
    }
}
