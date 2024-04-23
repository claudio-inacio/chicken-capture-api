<?php

namespace App\Providers;

use App\Interfaces\Authentication\AccessGroupRespositoryInterface;
use App\Interfaces\Authentication\CredentialRepositoryInterface;
use App\Interfaces\Authentication\PersonRespositoryInterface;
use App\Interfaces\Catch\CatchTypeRespositoryInterface;
use App\Interfaces\Catch\CatchsConfigurationRespositoryInterface;
use App\Interfaces\Catch\CatchsCancelledRespositoryInterface;
use App\Interfaces\Catch\CatchDailyRespositoryInterface;
use App\Interfaces\ContractingCompany\ContractingCompanyRepositoryInterface;
use App\Interfaces\ContractingCompany\IntegratedRepositoryInterface;
use App\Interfaces\Main\UnitsRepositoryInterface;
use App\Interfaces\Main\TeamRepositoryInterface;
use App\Interfaces\Main\CredentialCompanyRepositoryInterface;
use App\Interfaces\Main\CompanyRepositoryInterface;
use App\Interfaces\Main\CompanyGroupRepositoryInterface;
use App\Interfaces\Main\CollectorsRepositoryInterface;
use App\Interfaces\Financial\FinancialAccountsRepositoryInterface;
use App\Interfaces\Financial\MonthlyClosingReportsRepositoryInterface;
use App\Interfaces\Vehicles\VehiclesRepositoryInterface;
use App\Interfaces\Vehicles\DriverAreaRepositoryInterface;

use App\Repositories\Authentication\AccessGroupRepository;
use App\Repositories\Authentication\CredentialRepository;
use App\Repositories\Authentication\PersonRepository;
use App\Repositories\Catch\CatchTypeRepository;
use App\Repositories\Catch\CatchsConfigurationRepository;
use App\Repositories\Catch\CatchsCancelledRepository;
use App\Repositories\Catch\CatchDailyRepository;
use App\Repositories\ContractingCompany\ContractingCompanyRepository;
use App\Repositories\ContractingCompany\IntegratedRepository;
use App\Repositories\Main\UnitsRepository;
use App\Repositories\Main\TeamRepository;
use App\Repositories\Main\CredentialCompanyRepository;
use App\Repositories\Main\CompanyRepository;
use App\Repositories\Main\CompanyGroupRepository;
use App\Repositories\Main\CollectorsRepository;
use App\Repositories\Financial\FinancialAccountsRepository;
use App\Repositories\Financial\MonthlyClosingReportsRepository;
use App\Repositories\Vehicles\VehiclesRepository;
use App\Repositories\Vehicles\DriverAreaRepository;

use Illuminate\Support\ServiceProvider;


class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(AccessGroupRespositoryInterface::class, AccessGroupRepository::class);
        $this->app->bind(CredentialRepositoryInterface::class, CredentialRepository::class);
        $this->app->bind(PersonRespositoryInterface::class, PersonRepository::class);
        $this->app->bind(CatchTypeRespositoryInterface::class, CatchTypeRepository::class);
        $this->app->bind(CatchsConfigurationRespositoryInterface::class, CatchsConfigurationRepository::class);
        $this->app->bind(CatchsCancelledRespositoryInterface::class, CatchsCancelledRepository::class);
        $this->app->bind(CatchDailyRespositoryInterface::class, CatchDailyRepository::class);
        $this->app->bind(ContractingCompanyRepositoryInterface::class, ContractingCompanyRepository::class);
        $this->app->bind(IntegratedRepositoryInterface::class, IntegratedRepository::class);
        $this->app->bind(UnitsRepositoryInterface::class, UnitsRepository::class);
        $this->app->bind(TeamRepositoryInterface::class, TeamRepository::class);
        $this->app->bind(CredentialCompanyRepositoryInterface::class, CredentialCompanyRepository::class);
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->app->bind(CompanyGroupRepositoryInterface::class, CompanyGroupRepository::class);
        $this->app->bind(CollectorsRepositoryInterface::class, CollectorsRepository::class);
        $this->app->bind(FinancialAccountsRepositoryInterface::class, FinancialAccountsRepository::class);
        $this->app->bind(MonthlyClosingReportsRepositoryInterface::class, MonthlyClosingReportsRepository::class);
        $this->app->bind(VehiclesRepositoryInterface::class, VehiclesRepository::class);
        $this->app->bind(DriverAreaRepositoryInterface::class, DriverAreaRepository::class);

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
