<?php

namespace App\Http\Controllers\Api\Authentication;

use App\Http\Controllers\Controller;
use App\Services\Authentication\PersonService;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    public function register(Request $request) {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'phone_number' => 'required',
            'access_group_id' => 'required',
            'document' => 'required',
            'password' => 'required',
            'company_ids' => 'required',
        ]);

        return PersonService::create($request->all(), $request->user());
    }
}
