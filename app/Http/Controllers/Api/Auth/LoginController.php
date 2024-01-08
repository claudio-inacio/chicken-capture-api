<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Interfaces\Authentication\CredentialRepositoryInterface;
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
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if(empty($data['password']) || empty($data['document']))
            return response()->json(['message' => 'Informe seu login e senha!'], 422);

        $user = $this->credentialRepository->getByCpf($request->document);

        if(count($user) <= 0)
            return response()->json(['message' => 'Login ou senha incorreta!']);

        if (crypt($data['password'], $user[0]['password']) != $user[0]['password']) {
            return response()->json(['message' => 'Login ou senha incorreta!']);
        }

        $token = $this->respondWithToken($token);
        return response()->json([ 'token' => $token, 'user' => $user[0] ]);
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
