<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Integrated extends Model
{
    use HasFactory;

    protected $table = 'main.integrated';

    protected $fillable = ['id', 'name', 'city_id', 'enabled', 'created_at', 'updated_at'];
}
