<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ContractingCompany extends Model
{
    use HasFactory;

    protected $table = 'main.contracting_company';

    protected $fillable = ['id', 'name', 'company_id', 'enabled', 'created_at', 'updated_at'];
}
