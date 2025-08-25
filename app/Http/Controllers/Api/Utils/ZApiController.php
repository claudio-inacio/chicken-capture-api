<?php

namespace App\Http\Controllers\Api\Vehicles;

use App\Http\Controllers\Controller;
use App\Models\Authentication\Person;
use App\Models\Credential;
use App\Services\Main\ZApiService;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZApiController extends Controller
{
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required',
            'credential_id' => 'required',
        ]);

        $credential = Credential::find($request->credential_id);
        if (!$credential) return ResponseService::businessError('Credencial não encontrada.');

        $person = Person::find($credential->person_id);
        if (empty($person->phone_number)){
            return ResponseService::businessError("Por favor configure o telefone do usuario: {$person->name} - {$person->document}");
        }

        return ZApiService::sendMessage($request->message, $person->phone_number);
    }
}
