<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CompanyGroup extends Model
{
    use HasFactory;

    protected $table = 'main.company_group';

    protected $fillable = ['id', 'name', 'created_at', 'updated_at'];
}
