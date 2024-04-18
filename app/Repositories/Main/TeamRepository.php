<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Main\CredentialCompanyRepositoryInterface;
use App\Interfaces\Main\TeamRepositoryInterface;
use App\Models\ContractingCompany\Integrated;
use App\Models\Main\Team;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class TeamRepository implements TeamRepositoryInterface
{
    public function getAll()
    {
        return Team::all();
    }

    public function getByName(string $name)
    {
        return Team::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('main.team');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('team.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['team.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return Integrated::where('id',$id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        try {
            $catchType = Integrated::where('name', $value['name'])->first();
            if ($catchType) return ResponseService::businessError('Ja existe uma integraçao com esse nome!');

            Integrated::create($value);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar integraçao', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        unset($data['integrated_id']);
        try {
            $catchType = Integrated::where('name', $data['name'])
                ->where('id', '<>', $id)->first();

            if ($catchType) return ResponseService::businessError('Ja existe uma integraçao com esse nome!');

            Integrated::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em alterar integraçao', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            Integrated::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar integraçao', $e->getMessage());
        }
    }
}
