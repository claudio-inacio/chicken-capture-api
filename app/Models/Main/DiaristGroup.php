<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class DiaristGroup extends Model
{
    use HasFactory;

    protected $table = 'main.diarist_group';

    protected $fillable = ['id', 'function_name', 'salary', 'company_id', 'enabled', 'created_at', 'updated_at'];
}
