<?php

namespace App\Models\ContractingCompany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Integrated extends Model
{
    use HasFactory;

    protected $table = 'contracting_company.integrated';

    protected $fillable = ['id', 'name', 'contracting_company_id', 'enabled', 'created_at', 'updated_at'];
}
