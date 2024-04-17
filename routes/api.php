<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Authentication\PersonController;
use App\Http\Controllers\Api\Catch\CatchTypeController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::group(['middleware' => ['authJwt']], function (){
    ################################### MODULE AUTHENTICATION #############################
    Route::post('/person', [PersonController::class, 'register']);
    Route::get('/person', [PersonController::class, 'list']);
    Route::put('/person', [PersonController::class, 'update']);
    Route::put('/person/enable', [PersonController::class, 'enable']);

    ##################################### MODULE CATCH TYPE ###################################
    Route::post('/catch-type', [CatchTypeController::class, 'register']);
    Route::get('/catch-type', [CatchTypeController::class, 'list']);
    Route::put('/catch-type', [CatchTypeController::class, 'update']);
    Route::put('/catch-type/enable', [CatchTypeController::class, 'enable']);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/refresh', [LoginController::class, 'refreshToken']);
    Route::post('/logout', [LoginController::class, 'logout']);
});
