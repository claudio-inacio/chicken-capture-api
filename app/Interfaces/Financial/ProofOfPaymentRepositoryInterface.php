<?php
namespace App\Interfaces\Financial;

interface ProofOfPaymentRepositoryInterface
{
    public function selectByFinancial(int $financialId) : array;
    public function create(array $arrayData);
}
