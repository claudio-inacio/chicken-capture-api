<?php
namespace App\Interfaces\Vehicles;

use App\Models\Credential;

interface FuelSupplyRepositoryInterface
{
    public function findAll(array $selectConfig, array $whereCriterious, Credential $credential) : array;
    public function findAllByDate(array $selectConfig, $startDate, $endDate) : array;
    public function getById(int $id);
    public function create(array $value);
    public function update(int $id, array $data);
    public function enable(int $id, bool $enable);
}
