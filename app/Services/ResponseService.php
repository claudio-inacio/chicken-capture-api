<?php

namespace App\Services;


class ResponseService
{
    public static function reponse(bool $success, string $message, $error, int $code){
        return response()->json([
            'success' => $success,
            'message' => $message ?? '',
            'error' => $error ?? '',
        ],$code);
    }

    public static function success(string $message){
        return  response()->json([
            'success' => true,
            'message' => $message ?? ''
        ], 200);
    }

    public static function invalidArguments(string $message, $error){
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? ''
        ], 400);
    }

    public static function unauthenticated(string $message, $error){
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? ''
        ], 401);
    }

    public static function unauthorized(string $message, $error){
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? ''
        ], 403);
    }

    public static function notAcceptable(string $message, $error){
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? ''
        ], 406);
    }

    public static function timeout(string $message, $error){
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? ''
        ], 408);
    }

    public static function conflict(string $message, $error){
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? ''
        ], 409);
    }

    public static function businessError(string $message, $error){
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? ''
        ], 422);
    }

    public static function internalServerError(string $message, $error){
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? ''
        ], 500);
    }
}
