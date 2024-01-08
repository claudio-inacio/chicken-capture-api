<?php
namespace App\Interfaces\Authentication;

interface CredentialRepositoryInterface
{
    public function getAll();
    public function findAll(array $selectConfig, array $whereCriterious) : array;
    public function getByCpf(string $cpf);
    public function getById(int $id);
    public function create(array $value);
    public function update(int $id, array $data);
}
