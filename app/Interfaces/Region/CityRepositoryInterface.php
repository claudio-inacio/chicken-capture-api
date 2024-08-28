<?php
namespace App\Interfaces\Region;

interface CityRepositoryInterface
{
    public function findAll(array $selectConfig, array $whereCriterious) : array;
}
