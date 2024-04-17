<?php

namespace App\Models\ContractingCompany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ContractingCompany extends Model
{
    use HasFactory;

    protected $table = 'contracting_company.contracting_company';

    protected $fillable = ['id', 'name', 'company_id', 'enabled', 'created_at', 'updated_at'];
}
