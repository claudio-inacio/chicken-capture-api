<?php
namespace App\Interfaces\Authentication;

interface PersonRespositoryInterface
{
    public function getAll();
    public function getByName(string $name);
    public function findAll(array $selectConfig, array $whereCriterious) : array;
    public function getById(int $id);
    public function create(array $value);
    public function update(array $data);
    public function enable(int $id, bool $enabled);
}
