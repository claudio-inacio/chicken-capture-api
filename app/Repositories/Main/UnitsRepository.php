<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Main\UnitsRepositoryInterface;
use App\Models\Main\Units;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class UnitsRepository implements UnitsRepositoryInterface
{
    public function getAll()
    {
        return Units::all();
    }

    public function getByName(string $name)
    {
        return Units::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('main.units');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('units.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['units.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return Units::where('id',$id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        try {
            $units = Units::where('name', $value['name'])
                ->where('company_id', $value['company_id'])
                ->where('contracting_company_id', $value['contracting_company_id'])
                ->first();

            if ($units) return ResponseService::businessError('Ja existe uma unidade com esse nome!');

            Units::create($value);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar unidade', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        unset($data['units_id']);
        try {
            $units = Units::where('name', $data['name'])
                ->where('contracting_company_id', $data['contracting_company_id'])
                ->where('id', '<>', $id)->first();

            if ($units) return ResponseService::businessError('Ja existe uma unidade com esse nome!');

            Units::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em alterar unidade', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            Units::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar unidade', $e->getMessage());
        }
    }
}
