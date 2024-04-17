<?php

namespace App\Repositories\Catch;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Catch\CatchTypeRespositoryInterface;
use App\Models\Catch\CatchType;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class CatchTypeRepository implements CatchTypeRespositoryInterface
{
    public function getAll()
    {
        return CatchType::all();
    }

    public function getByName(string $name)
    {
        return CatchType::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('catch.catch_type');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('catch_type.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['catch_type.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return CatchType::where('id',$id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        try {
            $catchType = CatchType::where('name', $value['name']);
            if ($catchType) return ResponseService::businessError('Ja existe um tipo de apanha com esse nome!');

            CatchType::create($value);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar tipo de apanha', $e->getMessage());
        }
    }

    public function update(int $id, array $data)
    {
        return CatchType::whereId($id)->update($data);
    }
}
