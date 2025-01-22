<?php

namespace App\Services\Main;

use App\Models\Main\Log;

class LogService
{
    public static function save($text, $data = []){
        Log::create([
           'log' => $text,
           'error' => json_encode($data)
        ]);
    }
}
