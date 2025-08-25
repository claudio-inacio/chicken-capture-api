<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Authentication\DriverController;
use App\Http\Controllers\Api\Authentication\PersonController;

use App\Http\Controllers\Api\Catch\CatchTypeController;
use App\Http\Controllers\Api\Catch\CatchDailyController;
use App\Http\Controllers\Api\Catch\CatchCancelledController;
use App\Http\Controllers\Api\Catch\CatchConfigurationController;

use App\Http\Controllers\Api\Catch\ExtraCatchConfigurationController;
use App\Http\Controllers\Api\Financial\CostCenterController;
use App\Http\Controllers\Api\Financial\ProofOfPaymentController;
use App\Http\Controllers\Api\Main\ContractingCompanyController;
use App\Http\Controllers\Api\Main\IntegratedController;
use App\Http\Controllers\Api\Main\UnitsController;
use App\Http\Controllers\Api\Main\TeamController;
use App\Http\Controllers\Api\Main\CollectorsGroupCotroller;
use App\Http\Controllers\Api\Main\CollectorsController;
use App\Http\Controllers\Api\Main\CompanyController;
use App\Http\Controllers\Api\Main\CompanyGroupController;
use App\Http\Controllers\Api\Main\CredentialCompanyController;
use App\Http\Controllers\Api\Main\DiaristGroupController;
use App\Http\Controllers\Api\Main\DiaristController;

use App\Http\Controllers\Api\Financial\FinancialAccountsController;
use App\Http\Controllers\Api\Financial\MonthlyClosingReportsController;

use App\Http\Controllers\Api\Region\CityController;

use App\Http\Controllers\Api\Vehicles\FuelSupplyController;
use App\Http\Controllers\Api\Vehicles\VehiclesController;
use App\Http\Controllers\Api\Vehicles\DriverAreaController;

use App\Http\Controllers\Api\Vehicles\ZApiController;
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

    Route::post('/authentication/credential-company', [CredentialCompanyController::class, 'register']);
    Route::get('/authentication/credential-company', [CredentialCompanyController::class, 'list']);
    Route::put('/authentication/credential-company', [CredentialCompanyController::class, 'update']);
    Route::put('/authentication/credential-company/enable', [CredentialCompanyController::class, 'enable']);

    ##################################### CATCH ###################################
    Route::post('/catch/catch-type', [CatchTypeController::class, 'register']);
    Route::get('/catch/catch-type', [CatchTypeController::class, 'list']);
    Route::put('/catch/catch-type', [CatchTypeController::class, 'update']);
    Route::put('/catch/catch-type/enable', [CatchTypeController::class, 'enable']);

    Route::post('/catch/catch-daily', [CatchDailyController::class, 'register']);
    Route::get('/catch/catch-daily', [CatchDailyController::class, 'list']);
    Route::put('/catch/catch-daily', [CatchDailyController::class, 'update']);
    Route::put('/catch/catch-daily/enable', [CatchDailyController::class, 'enable']);
    Route::get('/catch/catch-daily/analytic', [CatchDailyController::class, 'analytic']);

    Route::post('/catch/catch-cancelled', [CatchCancelledController::class, 'register']);
    Route::get('/catch/catch-cancelled', [CatchCancelledController::class, 'list']);
    Route::put('/catch/catch-cancelled', [CatchCancelledController::class, 'update']);
    Route::put('/catch/catch-cancelled/enable', [CatchCancelledController::class, 'enable']);

    Route::post('/catch/catchs-configuration', [CatchConfigurationController::class, 'register']);
    Route::get('/catch/catchs-configuration', [CatchConfigurationController::class, 'list']);
    Route::put('/catch/catchs-configuration', [CatchConfigurationController::class, 'update']);
    Route::put('/catch/catchs-configuration/enable', [CatchConfigurationController::class, 'enable']);

    Route::post('/catch/extra-catch-configuration', [ExtraCatchConfigurationController::class, 'register']);
    Route::get('/catch/extra-catch-configuration', [ExtraCatchConfigurationController::class, 'list']);
    Route::get('/catch/extra-catch-configuration', [ExtraCatchConfigurationController::class, 'select']);
    Route::put('/catch/extra-catch-configuration', [ExtraCatchConfigurationController::class, 'update']);
    Route::put('/catch/extra-catch-configuration/enable', [ExtraCatchConfigurationController::class, 'enable']);

    ##################################### MAIN ###################################
    Route::post('/main/contracting-company', [ContractingCompanyController::class, 'register']);
    Route::get('/main/contracting-company', [ContractingCompanyController::class, 'list']);
    Route::put('/main/contracting-company', [ContractingCompanyController::class, 'update']);
    Route::put('/main/contracting-company/enable', [ContractingCompanyController::class, 'enable']);

    Route::post('/main/integrated', [IntegratedController::class, 'register']);
    Route::get('/main/integrated', [IntegratedController::class, 'list']);
    Route::put('/main/integrated', [IntegratedController::class, 'update']);
    Route::put('/main/integrated/enable', [IntegratedController::class, 'enable']);

    Route::post('/main/units', [UnitsController::class, 'register']);
    Route::get('/main/units', [UnitsController::class, 'list']);
    Route::put('/main/units', [UnitsController::class, 'update']);
    Route::put('/main/units/enable', [UnitsController::class, 'enable']);

    Route::post('/main/team', [TeamController::class, 'register']);
    Route::get('/main/team', [TeamController::class, 'list']);
    Route::put('/main/team', [TeamController::class, 'update']);
    Route::put('/main/team/enable', [TeamController::class, 'enable']);

    Route::post('/main/collectors-group', [CollectorsGroupCotroller::class, 'register']);
    Route::get('/main/collectors-group', [CollectorsGroupCotroller::class, 'list']);
    Route::put('/main/collectors-group', [CollectorsGroupCotroller::class, 'update']);
    Route::put('/main/collectors-group/enable', [CollectorsGroupCotroller::class, 'enable']);

    Route::post('/main/collectors', [CollectorsController::class, 'register']);
    Route::get('/main/collectors', [CollectorsController::class, 'list']);
    Route::get('/main/collectors/available', [CollectorsController::class, 'listAvailable']);
    Route::put('/main/collectors', [CollectorsController::class, 'update']);
    Route::put('/main/collectors/enable', [CollectorsController::class, 'enable']);

    Route::post('/main/diarist-group', [DiaristGroupController::class, 'register']);
    Route::get('/main/diarist-group', [DiaristGroupController::class, 'list']);
    Route::put('/main/diarist-group', [DiaristGroupController::class, 'update']);
    Route::put('/main/diarist-group/enable', [DiaristGroupController::class, 'enable']);

    Route::post('/main/diarist', [DiaristController::class, 'register']);
    Route::get('/main/diarist', [DiaristController::class, 'list']);
    Route::get('/main/diarist/select', [DiaristController::class, 'select']);
    Route::put('/main/diarist', [DiaristController::class, 'update']);
    Route::put('/main/diarist/enable', [DiaristController::class, 'enable']);

    Route::post('/main/company', [CompanyController::class, 'register']);
    Route::get('/main/company', [CompanyController::class, 'list']);
    Route::put('/main/company', [CompanyController::class, 'update']);
    Route::put('/main/company/enable', [CompanyController::class, 'enable']);

    Route::post('/main/company-group', [CompanyGroupController::class, 'register']);
    Route::get('/main/company-group', [CompanyGroupController::class, 'list']);
    Route::put('/main/company-group', [CompanyGroupController::class, 'update']);
    Route::put('/main/company-group/enable', [CompanyGroupController::class, 'enable']);

    #################################### FINANCIAL #########################################
    Route::post('/financial/financial-accounts', [FinancialAccountsController::class, 'register']);
    Route::get('/financial/financial-accounts', [FinancialAccountsController::class, 'list']);
    Route::get('/financial/financial-accounts/by-date', [FinancialAccountsController::class, 'listByDate']);
    Route::get('/financial/financial-accounts/download', [FinancialAccountsController::class, 'download']);
    Route::put('/financial/financial-accounts', [FinancialAccountsController::class, 'update']);
    Route::put('/financial/financial-accounts/enable', [FinancialAccountsController::class, 'enable']);
    Route::get('/financial/financial-accounts/analytic', [FinancialAccountsController::class, 'analytic']);
    Route::get('/financial/financial-accounts/general-report', [FinancialAccountsController::class, 'generalReport']);

    Route::post('/financial/monthly-closing-reports', [MonthlyClosingReportsController::class, 'register']);
    Route::get('/financial/monthly-closing-reports', [MonthlyClosingReportsController::class, 'list']);

    Route::post('/financial/cost-center', [CostCenterController::class, 'register']);
    Route::get('/financial/cost-center', [CostCenterController::class, 'list']);
    Route::put('/financial/cost-center', [CostCenterController::class, 'update']);
    Route::put('/financial/cost-center/enable', [CostCenterController::class, 'enable']);

    Route::post('/financial/proof-payment', [ProofOfPaymentController::class, 'create']);
    Route::get('/financial/proof-payment', [ProofOfPaymentController::class, 'list']);

    ################################## REGION ############################################
    Route::get('/region/city', [CityController::class, 'list']);

    ################################### VEHICLES ###########################################
    Route::post('/vehicles/vehicle', [VehiclesController::class, 'register']);
    Route::get('/vehicles/vehicle', [VehiclesController::class, 'list']);
    Route::put('/vehicles/vehicle', [VehiclesController::class, 'update']);
    Route::put('/vehicles/vehicle/enable', [VehiclesController::class, 'enable']);
    Route::get('/vehicles/expenses', [VehiclesController::class, 'expenses']);

    Route::post('/vehicles/driver-area', [DriverAreaController::class, 'register']);
    Route::get('/vehicles/driver-area', [DriverAreaController::class, 'list']);
    Route::put('/vehicles/driver-area', [DriverAreaController::class, 'update']);
    Route::put('/vehicles/driver-area/finalize', [DriverAreaController::class, 'finalize']);
    Route::put('/vehicles/driver-area/enable', [DriverAreaController::class, 'enable']);
    Route::get('/vehicles/driver-area/analytic', [DriverAreaController::class, 'analytic']);
    Route::get('/vehicles/driver-area/init-day/analytic', [DriverAreaController::class, 'initDayAnalytic']);
    Route::get('/vehicles/driver-area/time-to-init/analytic', [DriverAreaController::class, 'timeToInitAnalytic']);

    Route::post('/vehicles/fuel-supply', [FuelSupplyController::class, 'register']);
    Route::get('/vehicles/fuel-supply', [FuelSupplyController::class, 'list']);
    Route::get('/vehicles/fuel-supply/by-date', [FuelSupplyController::class, 'listByDate']);
    Route::put('/vehicles/fuel-supply', [FuelSupplyController::class, 'update']);
    Route::put('/vehicles/fuel-supply/enable', [FuelSupplyController::class, 'enable']);

    ##################################### DRIVER ############################################
    Route::get('/authentication/credential/available-driver', [DriverController::class, 'listAvailableDriver']);

    ##################################### Z-API #############################################
    Route::post('/z-api/send-message', [ZApiController::class, 'sendMessage']);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/refresh', [LoginController::class, 'refreshToken']);
    Route::post('/logout', [LoginController::class, 'logout']);
});
