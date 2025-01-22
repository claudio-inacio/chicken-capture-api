<?php

namespace App\Http\Controllers\Api\Authentication;

use App\Helpers\FormatHelper;
use App\Http\Controllers\Controller;
use App\Interfaces\Authentication\CredentialRepositoryInterface;
use App\Interfaces\Main\DiaristRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    private CredentialRepositoryInterface $credentialRepository;

    public function __construct
    (
        CredentialRepositoryInterface $credentialRepository
    )
    {
        $this->credentialRepository = $credentialRepository;
    }

    public function listAvailableDriver(Request $request): JsonResponse
    {
       return response()->json($this->credentialRepository->listAvailableDriver($request->user()));
    }
}
