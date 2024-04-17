<?php
namespace App\Interfaces\ContractingCompany;

interface MonthlyClosingReportsRepositoryInterface
{
    public function getAll();
    public function getByName(string $name);
    public function findAll(array $selectConfig, array $whereCriterious) : array;
    public function getById(int $id);
    public function create(array $value);
    public function update(int $id, array $data);
}
