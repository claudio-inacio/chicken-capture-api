<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Log extends Model
{
    use HasFactory;

    protected $table = 'main.log';

    protected $fillable = [
        'id',
        'log',
        'error',
        'created_at',
        'updated_at'
    ];
}
