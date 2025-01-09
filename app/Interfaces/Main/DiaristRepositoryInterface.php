<?php
namespace App\Interfaces\Main;

use App\Models\Credential;

interface DiaristRepositoryInterface
{
    public function getAll();
    public function findAll(array $selectConfig, array $whereCriterious, $credential) : array;
    public function select(array $arrayData) : array;
    public function create(array $arrayData, $credential);
    public function update(int $id, array $arrayData);
    public function enable(int $id, bool $enable);
}
