<?php
namespace App\Interfaces\Catch;

interface ExtraCatchRepositoryInterface
{
    public function getAll();
    public function findAll(array $selectConfig, array $whereCriterious) : array;
    public function getById(int $id);
    public function create(array $value);
    public function update(int $id, array $data);
    public function enable(int $id, bool $enable);
}
