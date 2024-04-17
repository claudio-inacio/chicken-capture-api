<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Interfaces\Authentication\CredentialRepositoryInterface;
use App\Models\Authentication\Person;
use App\Services\ResponseService;
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
        return response()->json([ 'token' => $token, 'user' => $user[0] ]);
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
