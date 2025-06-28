<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enum\Authentication\AccessGroupEnum;
use App\Http\Controllers\Controller;
use App\Interfaces\Authentication\CredentialRepositoryInterface;
use App\Models\Authentication\Person;
use App\Models\Vehicles\DriverArea;
use App\Models\Vehicles\Vehicle;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    private CredentialRepositoryInterface $credentialRepository;

    public function __construct(
        CredentialRepositoryInterface $credentialRepository,
    )
    {
        $this->credentialRepository = $credentialRepository;
    }

    public function login(Request $request) {
        $data = $request->only('document', 'password');

        if (! $token = auth('api')->attempt($data)) {
            return ResponseService::unauthenticated('Unauthorized', '');
        }

        if(empty($data['password']) || empty($data['document']))
            return ResponseService::businessError('Informe seu login e senha!', ['data' => $data]);

        $user = $this->credentialRepository->getByCpf($request->document);

        if(count($user) <= 0)
            return ResponseService::businessError('Login ou senha incorreta!', '');

        $person = Person::whereId($user[0]['person_id'])->first();
        if (!$person->enabled)
            return ResponseService::businessError('Usuario Desativado!');

        if (crypt($data['password'], $user[0]['password']) != $user[0]['password']) {
            return ResponseService::businessError('Login ou senha incorreta!', '');
        }

        $token = $this->respondWithToken($token);
        $responseBody = [
            'token' => $token,
            'user' => $user[0],

        ];

        if($user[0]["access_group_id"] == AccessGroupEnum::DRIVER){
            $driverArea = DriverArea::join('vehicles.vehicle', 'vehicle.id', '=', 'driver_area.vehicle_id')
                ->where('credential_id', $user[0]['id'])
                ->whereDate('driver_area.created_at', Carbon::today())
                ->select([
                    'driver_area.*',
                    'vehicle.name as vehicle_name', 'vehicle.plate_number as vehicle_plate_number'
                    ])
                ->first();

            $driverAreaFinalize = DriverArea::where('credential_id', $user[0]['id'])
                ->whereDate('driver_area.created_at', Carbon::today())
                ->where('driver_area.daily_end_date', '<>', null)
                ->first();

            $responseBody["dayStarted"] = !is_null($driverArea);
            $responseBody["dayFinalized"] = !is_null($driverAreaFinalize);
            $responseBody["driverArea"] =  $driverArea ? $driverArea->toArray() : [];
        }

        return response()->json($responseBody);
    }

    public function logout(){
        auth('api')->logout();

        return ResponseService::success('Logout efetuado com sucesso!');
    }

    public function refreshToken(){
        $newToken = auth('api')->refresh();
        $token = $this->respondWithToken($newToken);

        return response()->json(['token' => $token]);
    }

    protected function respondWithToken($token): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }
}
