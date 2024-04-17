<?php

namespace App\Http\Controllers\Api\Authentication;

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
            'name' => 'required',
            'email' => 'required',
            'phone_number' => 'required',
            'access_group_id' => 'required',
            'company_id' => 'required',
            'person_id' => 'required'
        ]);

        return $this->personRespository->update($request->person_id, $request->all());
    }
}
