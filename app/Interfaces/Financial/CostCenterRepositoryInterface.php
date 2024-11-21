<?php
namespace App\Interfaces\Financial;

interface CostCenterRepositoryInterface
{
    public function getAll();
    public function findAll(array $selectConfig, array $whereCriterious) : array;
    public function create(array $value);
    public function update(int $id, array $data);
    public function enable(int $id, bool $enable);
}
