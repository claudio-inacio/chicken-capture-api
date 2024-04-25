<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Authentication\PersonController;

use App\Http\Controllers\Api\Catch\CatchTypeController;
use App\Http\Controllers\Api\Catch\CatchDailyController;
use App\Http\Controllers\Api\Catch\CatchCancelledController;
use App\Http\Controllers\Api\Catch\CatchConfigurationController;

use App\Http\Controllers\Api\ContractingCompany\ContractingCompanyController;
use App\Http\Controllers\Api\ContractingCompany\IntegratedController;

use App\Http\Controllers\Api\Main\UnitsController;
use App\Http\Controllers\Api\Main\TeamController;
use App\Http\Controllers\Api\Main\CollectorsController;
use App\Http\Controllers\Api\Main\CompanyController;
use App\Http\Controllers\Api\Main\CompanyGroupController;
use App\Http\Controllers\Api\Main\CredentialCompanyController;

use App\Http\Controllers\Api\Financial\FinancialAccountsController;
use App\Http\Controllers\Api\Financial\MonthlyClosingReportsController;

use App\Http\Controllers\Api\Vehicles\VehiclesController;
use App\Http\Controllers\Api\Vehicles\DriverAreaController;

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

    Route::post('/catch/catch-daily', [CatchDailyController::class, 'register']);
    Route::get('/catch/catch-daily', [CatchDailyController::class, 'list']);
    Route::put('/catch/catch-daily', [CatchDailyController::class, 'update']);
    Route::put('/catch/catch-daily/enable', [CatchDailyController::class, 'enable']);
    Route::put('/catch/catch-daily/analytic', [CatchDailyController::class, 'analytic']);

    Route::post('/catch/catch-cancelled', [CatchCancelledController::class, 'register']);
    Route::get('/catch/catch-cancelled', [CatchCancelledController::class, 'list']);
    Route::put('/catch/catch-cancelled', [CatchCancelledController::class, 'update']);
    Route::put('/catch/catch-cancelled/enable', [CatchCancelledController::class, 'enable']);

    Route::post('/catch/catchs-configuration', [CatchConfigurationController::class, 'register']);
    Route::get('/catch/catchs-configuration', [CatchConfigurationController::class, 'list']);
    Route::put('/catch/catchs-configuration', [CatchConfigurationController::class, 'update']);
    Route::put('/catch/catchs-configuration/enable', [CatchConfigurationController::class, 'enable']);

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

    Route::post('/main/team', [TeamController::class, 'register']);
    Route::get('/main/team', [TeamController::class, 'list']);
    Route::put('/main/team', [TeamController::class, 'update']);
    Route::put('/main/team/enable', [TeamController::class, 'enable']);

    Route::post('/main/collectors', [CollectorsController::class, 'register']);
    Route::get('/main/collectors', [CollectorsController::class, 'list']);
    Route::put('/main/collectors', [CollectorsController::class, 'update']);
    Route::put('/main/collectors/enable', [CollectorsController::class, 'enable']);

    Route::post('/main/company', [CompanyController::class, 'register']);
    Route::get('/main/company', [CompanyController::class, 'list']);
    Route::put('/main/company', [CompanyController::class, 'update']);
    Route::put('/main/company/enable', [CompanyController::class, 'enable']);

    Route::post('/main/company-group', [CompanyGroupController::class, 'register']);
    Route::get('/main/company-group', [CompanyGroupController::class, 'list']);
    Route::put('/main/company-group', [CompanyGroupController::class, 'update']);
    Route::put('/main/company-group/enable', [CompanyGroupController::class, 'enable']);

    Route::post('/main/credential-company', [CredentialCompanyController::class, 'register']);
    Route::get('/main/credential-company', [CredentialCompanyController::class, 'list']);
    Route::put('/main/credential-company', [CredentialCompanyController::class, 'update']);
    Route::put('/main/credential-company/enable', [CredentialCompanyController::class, 'enable']);

    #################################### FINANCIAL #########################################
    Route::post('/financial/financial-accounts', [FinancialAccountsController::class, 'register']);
    Route::get('/financial/financial-accounts', [FinancialAccountsController::class, 'list']);
    Route::put('/financial/financial-accounts', [FinancialAccountsController::class, 'update']);
    Route::put('/financial/financial-accounts/enable', [FinancialAccountsController::class, 'enable']);
    Route::put('/financial/financial-accounts/analytic', [FinancialAccountsController::class, 'analytic']);

    Route::post('/financial/monthly-closing-reports', [MonthlyClosingReportsController::class, 'register']);
    Route::get('/financial/monthly-closing-reports', [MonthlyClosingReportsController::class, 'list']);

    ################################### VEHICLES ###########################################
    Route::post('/vehicles/vehicle', [VehiclesController::class, 'register']);
    Route::get('/vehicles/vehicle', [VehiclesController::class, 'list']);
    Route::put('/vehicles/vehicle', [VehiclesController::class, 'update']);
    Route::put('/vehicles/vehicle/enable', [VehiclesController::class, 'enable']);

    Route::post('/vehicles/driver-area', [DriverAreaController::class, 'register']);
    Route::get('/vehicles/driver-area', [DriverAreaController::class, 'list']);
    Route::put('/vehicles/driver-area', [DriverAreaController::class, 'update']);
    Route::put('/vehicles/driver-area/finalize', [DriverAreaController::class, 'finalize']);
    Route::put('/vehicles/driver-area/enable', [DriverAreaController::class, 'enable']);
    Route::put('/vehicles/driver-area/analytic', [DriverAreaController::class, 'analytic']);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/refresh', [LoginController::class, 'refreshToken']);
    Route::post('/logout', [LoginController::class, 'logout']);
});
