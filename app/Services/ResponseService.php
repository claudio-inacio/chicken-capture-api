<?php

namespace App\Services;


use Illuminate\Http\JsonResponse;

class ResponseService
{
    public static function reponse(bool $success, string $message, $error, int $code): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message ?? '',
            'error' => $error ?? '',
        ],$code);
    }

    public static function success(string $message, mixed $data = ''): JsonResponse
    {
        return  response()->json([
            'success' => true,
            'message' => $message ?? '',
            'data' => $data ?? ''
        ], 200);
    }

    public static function successWithTotaL(string $message, mixed $data = '', int $total = 0): JsonResponse
    {
        return  response()->json([
            'success' => true,
            'message' => $message ?? '',
            'data' => $data ?? '',
            'total' => $total ?? 0
        ], 200);
    }

    public static function success204(): JsonResponse
    {
        return  response()->json('', 204);
    }

    public static function invalidArguments(string $message = 'Argumento invalidos.',string $error = ''): JsonResponse
    {
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? ''
        ], 400);
    }

    public static function unauthenticated(string $message, $error = ''): JsonResponse
    {
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'error' => $error
        ], 401);
    }

    public static function unauthorized(string $message, $error = ''): JsonResponse
    {
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'error' => $error
        ], 403);
    }

    public static function notAcceptable(string $message, $error): JsonResponse
    {
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? ''
        ], 406);
    }

    public static function timeout(string $message, $error): JsonResponse
    {
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? ''
        ], 408);
    }

    public static function conflict(string $message, $error): JsonResponse
    {
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'error' => $error ?? ''
        ], 409);
    }

    public static function businessError(string $message, mixed $error = ''): JsonResponse
    {
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'error' => $error
        ], 422);
    }

    public static function businessErrorWithData(string $message, mixed $data = ''): JsonResponse
    {
        return  response()->json([
            'success' => false,
            'message' => $message ?? '',
            'data' => $data
        ], 422);
    }

    public static function internalServerError(string $message = 'Erro interno no servidor.', mixed $error = ''): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $error
        ], 500);
    }
}
