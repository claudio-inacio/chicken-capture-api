<?php
namespace App\Interfaces\Financial;

interface FinancialAccountsRepositoryInterface
{
    public function getAll();
    public function getByName(string $name);
    public function findAll(array $selectConfig, array $whereCriterious) : array;
    public function findAllDownload(array $selectConfig, array $whereCriterious) : array;
    public function getById(int $id);
    public function create(array $arrayData, array $paymentData);
    public function update(int $id, array $data, array $paymentData);
    public function enable(int $id, bool $enable);
    public function generalReport(array $selectConfig, array $whereCriterious) : array;
}
