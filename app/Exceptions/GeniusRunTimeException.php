<?php

namespace App\Exceptions;

use JetBrains\PhpStorm\Pure;
use RuntimeException;

class GeniusRunTimeException extends RuntimeException
{
    protected array $data;

    #[Pure] public function __construct(string $message = "", array $data = [], int $code = 500, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }

    public function render($request): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'info_data' => $this->data,
        ], $this->code ?: 500);
    }
}
