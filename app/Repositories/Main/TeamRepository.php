<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Main\TeamRepositoryInterface;
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
        $query = DB::table('main.team')
            ->join('main.company', 'company.id', '=', 'team.company_id')
            ->join('main.units', 'units.id', '=', 'team.default_unit_id')
            ->join('contracting_company.contracting_company', 'contracting_company.id', '=', 'team.contracting_company_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('team.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'team.*',
            'company.name as company',
            'units.name as unit_name','units.location as unit_location',
            'contracting_company.name as contracting_company'
        ]);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return Team::where('id',$id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        try {
            $team = Team::where('name', $value['name'])
                ->where('company_id', $value['company_id'])
                ->first();

            if ($team) return ResponseService::businessError('Ja existe um time com esse nome!');

            Team::create($value);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar time', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        unset($data['team_id']);
        try {
            $team = Team::where('name', $data['name'])
                ->where('id', '<>', $id)->first();

            if ($team) return ResponseService::businessError('Ja existe um time com esse nome!');

            Team::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em alterar time', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            Team::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar time', $e->getMessage());
        }
    }
}
