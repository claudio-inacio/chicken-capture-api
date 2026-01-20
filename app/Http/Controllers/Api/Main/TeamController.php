<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use App\Interfaces\Main\TeamRepositoryInterface;
use App\Models\Main\ContractingCompany;
use App\Models\Main\Team;
use App\Models\Main\Units;
use App\Services\Main\CollectorsService;
use App\Services\ResponseService;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    private TeamRepositoryInterface $teamRepository;

    public function __construct
    (
        TeamRepositoryInterface $teamRepository
    )
    {
        $this->teamRepository = $teamRepository;
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required',
            'default_unit_id' => 'required',
            'collectors' => 'required',
            'contracting_company_id' => 'required',
            "driver_credential_id" => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['driver_credential_id'] = $request->driver_credential_id;
        $arrayData['company_id'] = $request->user()->company_id;

        $verifyTeamDriver = Team::where('driver_credential_id', $request->driver_credential_id)
            ->first();

        if ($verifyTeamDriver){
            return ResponseService::businessError("Motorista selecionado ja pertence a equipe: {$verifyTeamDriver->name}");
        }

        $verifyTeamLeader = Team::where('driver_credential_id', $request->driver_credential_id)
            ->first();

        if ($verifyTeamLeader){
            return ResponseService::businessError("Lider selecionado ja pertence a equipe: {$verifyTeamLeader->name}");
        }

        $verifyUnits = Units::find($request->default_unit_id);
        if (!$verifyUnits) return ResponseService::invalidArguments('Unidade não encontrada!');

        $contractingCompany = ContractingCompany::find($request->contracting_company_id);
        if (!$contractingCompany) return ResponseService::invalidArguments('Empresa contratante não encontrada!');

//        $verify = CollectorsService::verifyQuantityCollectors($arrayData, $request->user());
//        if (!$verify['success']) {
//            return ResponseService::businessError($verify['message'], $verify['error']);
//        }

        return $this->teamRepository->create($arrayData);
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->teamRepository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'name' => 'required',
            'default_unit_id' => 'required',
            'collectors' => 'required',
            'contracting_company_id' => 'required',
            'team_id' => 'required',
            'driver_credential_id' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['driver_credential_id'] = $request->driver_credential_id;
        $arrayData['company_id'] = $request->user()->company_id;

        $verifyTeamDriver = Team::where('driver_credential_id', $request->driver_credential_id)
            ->where('team.id', '<>', $request->team_id)
            ->first();

        if ($verifyTeamDriver){
            return ResponseService::businessError("Motorista selecionado ja pertence a equipe: {$verifyTeamDriver->name}");
        }

        $verifyTeamLeader = Team::where('driver_credential_id', $request->driver_credential_id)
            ->where('team.id', '<>', $request->team_id)
            ->first();

        if ($verifyTeamLeader){
            return ResponseService::businessError("Lider selecionado ja pertence a equipe: {$verifyTeamLeader->name}");
        }

        $verify = CollectorsService::verifyQuantityCollectors($arrayData, $request->user());
        if (!$verify['success']) {
            return ResponseService::businessError($verify['message'], $verify['error']);
        }

        return $this->teamRepository->update($request->team_id, $request->all());
    }

    public function enable(Request $request){
        $request->validate([
            'team_id' => 'required',
            'enabled' => 'required'
        ]);

        return $this->teamRepository->enable($request->team_id, $request->enabled);
    }
}
