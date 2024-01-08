<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ResponseService;
use Illuminate\Http\Request;

set_time_limit(0);
ini_set('memory_limit', -1);

class TestController extends Controller
{

    public function test(Request $request){
        $teste = false;

        if (!$teste)
            $response = ResponseService::reponse(false, 'Teste de erro ok', ['Bug' => 'the Bug is on the table'], 422);


         return response()->json($response, $response['code']);
    }
}
