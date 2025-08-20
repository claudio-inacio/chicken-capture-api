<?php
namespace App\Interfaces\Financial;

use App\Models\Credential;
use Illuminate\Http\JsonResponse;

interface FinancialAccountsRepositoryInterface
{
    public function getAll();
    public function getByName(string $name);
    public function findAll(array $selectConfig, array $whereCriterious, Credential $credential) : array;
    public function findAllByDate(array $selectConfig, array $whereCriterious, $startDate, $endDate) : array;
    public function findAllDownload(array $selectConfig, array $whereCriterious) : array;
    public function getById(int $id);
    public function create(array $arrayData, array $paymentData);
    public function update(int $id, array $data, array $paymentData): JsonResponse;
    public function enable(int $id, bool $enable);
    public function generalReport(array $selectConfig, array $whereCriterious) : array;
}
