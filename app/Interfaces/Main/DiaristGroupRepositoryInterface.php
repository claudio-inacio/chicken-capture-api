<?php
namespace App\Interfaces\Main;

interface DiaristGroupRepositoryInterface
{
    public function getAll();
    public function findAll(array $selectConfig, array $whereCriterious, $credential) : array;
    public function create(array $arrayData);
    public function update(int $id, array $data);
    public function enable(int $id, bool $enable);
}
