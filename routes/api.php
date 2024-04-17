<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Authentication\PersonController;

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

    ####################################### MODULE MAIN ###################################
    Route::post('/teste', [TestController::class, 'test']);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/refresh', [LoginController::class, 'refreshToken']);
    Route::post('/logout', [LoginController::class, 'logout']);
});
