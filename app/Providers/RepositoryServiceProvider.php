<?php

namespace App\Providers;

use App\Interfaces\Authentication\AccessGroupRespositoryInterface;
use App\Interfaces\Authentication\CredentialRepositoryInterface;
use App\Interfaces\Authentication\PersonRespositoryInterface;

use App\Repositories\Authentication\AccessGroupRepository;
use App\Repositories\Authentication\CredentialRepository;
use App\Repositories\Authentication\PersonRepository;

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
