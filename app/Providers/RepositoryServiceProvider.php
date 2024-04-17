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

use App\Repositories\Authentication\AccessGroupRepository;
use App\Repositories\Authentication\CredentialRepository;
use App\Repositories\Authentication\PersonRepository;
use App\Repositories\Catch\CatchTypeRepository;
use App\Repositories\Catch\CatchsConfigurationRepository;
use App\Repositories\Catch\CatchsCancelledRepository;
use App\Repositories\Catch\CatchDailyRepository;
use App\Repositories\ContractingCompany\ContractingCompanyRepository;
use App\Repositories\ContractingCompany\IntegratedRepository;

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
