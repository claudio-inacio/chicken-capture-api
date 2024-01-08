<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Routing\ResponseFactory;
class ApiResponseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $response = app(ResponseFactory::class);

        $response->macro('success', function ($status, $code, $data = null) use ($response) {
            $responseData = [
                'status' => $status,
                'code' => $code,
                'data' => $data
            ];

            return $response->json($responseData, $status);
        });

        $response->macro('error', function ($status, $code, $errors) use ($response) {

            if (is_string($errors)) {
                return $response->json([
                    'status' => $status,
                    'code' => $code,
                    'errors' => [$errors],
                ], $status);
            }

            $flatten = [];
            array_walk_recursive($errors, function ($error) use (&$flatten) {
                $flatten[] = $error;
            });

            return $response->json([
                'status' => $status,
                'code' => $code,
                'errors' => $flatten,
            ], $status);
        });
    }
}
