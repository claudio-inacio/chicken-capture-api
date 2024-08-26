<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Diarist extends Model
{
    use HasFactory;

    protected $table = 'main.diarist';

    protected $fillable = ['id', 'function_name', 'name', 'salary', 'document', 'phone_number', 'diarist_group_id',
        'company_id', 'enabled', 'created_at', 'updated_at'];
}
