<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Company extends Model
{
    use HasFactory;

    protected $table = 'main.company';

    protected $fillable = ['id', 'name', 'address', 'phone', 'cnpj', 'email', 'company_group_id', 'parent_id', 'is_main',
       'enabled', 'created_at', 'updated_at'];
}
