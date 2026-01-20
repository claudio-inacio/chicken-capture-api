<?php

namespace App\Http\Controllers\Api\Authentication;

use App\Enum\Authentication\AccessGroupEnum;
use App\Http\Controllers\Controller;
use App\Interfaces\Authentication\PersonRespositoryInterface;
use App\Services\Authentication\PersonService;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    private PersonRespositoryInterface $personRespository;

    public function __construct
    (
        PersonRespositoryInterface $personRespository
    )
    {
        $this->personRespository = $personRespository;
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'phone_number' => 'required',
            'access_group_id' => 'required',
            'document' => 'required',
            'password' => 'required',
            'company_id' => 'required',
        ]);

        if ($request->access_group_id == AccessGroupEnum::DRIVER ||
            $request->access_group_id == AccessGroupEnum::FINANCIAL ||
            $request->access_group_id == AccessGroupEnum::LEADER
        ) $request->validate(['salary' => 'required']);

        return PersonService::create($request->all(), $request->user());
    }

    public function list(Request $request){
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->personRespository->findAll($selectConfig, $whereCriterious));
    }

    public function update(Request $request){
        $request->validate([
            'person.name' => 'required',
            'person.email' => 'required',
            'person.phone_number' => 'required',
            'person.salary' => 'required',
            'person.person_id' => 'required',
            'credential.company_id' => 'required',
            'credential.document' => 'required',
            'credential.credential_id' => 'required',
            'credential.access_group_id' => 'required',
        ]);

        $arrayData = $request->all();

        $accessGroupId = $arrayData['credential']['access_group_id'];

        if ($accessGroupId == AccessGroupEnum::DRIVER ||
            $accessGroupId == AccessGroupEnum::FINANCIAL ||
            $accessGroupId == AccessGroupEnum::LEADER
        ) $request->validate(['person.salary' => 'required']);

        return $this->personRespository->update($request->all());
    }

    public function enable(Request $request){
        $request->validate([
            'person_id' => 'required',
            'enabled' => 'required'
        ]);

        return $this->personRespository->enable($request->person_id, $request->enabled);
    }
}
