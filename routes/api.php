<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Authentication\PersonController;
use App\Http\Controllers\Api\Catch\CatchTypeController;
use App\Http\Controllers\Api\ContractingCompany\ContractingCompanyController;
use App\Http\Controllers\Api\ContractingCompany\IntegratedController;
use App\Http\Controllers\Api\Main\UnitsController;

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
    ################################### AUTHENTICATION #############################
    Route::post('/authentication/person', [PersonController::class, 'register']);
    Route::get('/authentication/person', [PersonController::class, 'list']);
    Route::put('/authentication/person', [PersonController::class, 'update']);
    Route::put('/authentication/person/enable', [PersonController::class, 'enable']);

    ##################################### CATCH ###################################
    Route::post('/catch/catch-type', [CatchTypeController::class, 'register']);
    Route::get('/catch/catch-type', [CatchTypeController::class, 'list']);
    Route::put('/catch/catch-type', [CatchTypeController::class, 'update']);
    Route::put('/catch/catch-type/enable', [CatchTypeController::class, 'enable']);

    ##################################### CONTRACTING COMPANY ###################################
    Route::post('/contracting-company', [ContractingCompanyController::class, 'register']);
    Route::get('/contracting-company', [ContractingCompanyController::class, 'list']);
    Route::put('/contracting-company', [ContractingCompanyController::class, 'update']);
    Route::put('/contracting-company/enable', [ContractingCompanyController::class, 'enable']);

    Route::post('/contracting-company/integrated', [IntegratedController::class, 'register']);
    Route::get('/contracting-company/integrated', [IntegratedController::class, 'list']);
    Route::put('/contracting-company/integrated', [IntegratedController::class, 'update']);
    Route::put('/contracting-company/integrated/enable', [IntegratedController::class, 'enable']);

    ##################################### MAIN ###################################
    Route::post('/main/units', [UnitsController::class, 'register']);
    Route::get('/main/units', [UnitsController::class, 'list']);
    Route::put('/main/units', [UnitsController::class, 'update']);
    Route::put('/main/units/enable', [UnitsController::class, 'enable']);

//    Route::post('/catch-type', [CatchTypeController::class, 'register']);
//    Route::get('/catch-type', [CatchTypeController::class, 'list']);
//    Route::put('/catch-type', [CatchTypeController::class, 'update']);
//    Route::put('/catch-type/enable', [CatchTypeController::class, 'enable']);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/refresh', [LoginController::class, 'refreshToken']);
    Route::post('/logout', [LoginController::class, 'logout']);
});
