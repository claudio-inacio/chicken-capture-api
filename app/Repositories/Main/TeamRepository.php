<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Main\TeamRepositoryInterface;
use App\Models\Main\CollectorsGroup;
use App\Models\Main\Team;
use App\Services\Main\CollectorsService;
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
            ->join('authentication.credential as credential_driver', 'credential_driver.id', '=', 'team.driver_credential_id')
            ->join('authentication.credential as credential_leader', 'credential_leader.id', '=', 'team.leader_credential_id')
            ->join('authentication.person as person_driver', 'person_driver.id', '=', 'credential_driver.person_id')
            ->join('authentication.person as person_leader', 'person_leader.id', '=', 'credential_leader.person_id')
            ->join('main.units', 'units.id', '=', 'team.default_unit_id')
            ->join('main.contracting_company', 'contracting_company.id', '=', 'team.contracting_company_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('team.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'team.*',
            'company.name as company_name',
            'units.name as default_unit_name', 'units.code as unit_code',
            'contracting_company.name as contracting_company_name',
            "person_leader.name as leader_credential_name",
            "person_driver.name as driver_credential_name",
        ]);
        $result = $query->get()->toArray();

        foreach ($result as $item){
            $item->collectors = json_decode($item->collectors, true);
            foreach ($item->collectors as $keyCollector => $groupsCollector){
                foreach ($groupsCollector as $keyGroup => $group) {
                    $collectorGroup = CollectorsGroup::find($group['id']);
                    if ($collectorGroup) { // Verifica se o registro foi encontrado
                        $item->collectors[$keyCollector][$keyGroup] = array_merge(
                            $item->collectors[$keyCollector][$keyGroup],
                            ['function_name' => $collectorGroup['function_name']]
                        );
                    } else {
                        // Adicione um valor padrão ou ignore a adição
                        $item->collectors[$keyCollector][$keyGroup]['function_name'] = null;
                    }
                }
            }
        }

        return [
            'data' => $result,
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
            DB::beginTransaction();
            $team = Team::where('name', $value['name'])
                ->where('company_id', $value['company_id'])
                ->first();

            if ($team){
                DB::rollBack();
                return ResponseService::businessError('Ja existe um time com esse nome!');
            }

            $addCollectors = CollectorsService::addNewsCollectors($value['collectors']);
            if (!$addCollectors['success']) {
                DB::rollBack();
                return ResponseService::businessError($addCollectors['message'], $addCollectors['error']);
            }

            $value['collectors'] = json_encode($value['collectors']);
            Team::create($value);

            DB::commit();
            return ResponseService::success204();
        } catch (\Exception $e){
            DB::rollBack();
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

            $addCollectors = CollectorsService::removeCollectors($data['collectors'], $id);
            if (!$addCollectors['success']) {
                DB::rollBack();
                return ResponseService::businessError($addCollectors['message'], $addCollectors['error']);
            }

            $data['collectors'] = json_encode($data['collectors']);

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
