<?php
namespace App\Interfaces\Main;

interface CompanyGroupRepositoryInterface
{
    public function getAll();
    public function getByName(string $name);
    public function findAll(array $selectConfig, array $whereCriterious) : array;
    public function getById(int $id);
    public function create(array $value);
    public function update(int $id, array $data);
}
