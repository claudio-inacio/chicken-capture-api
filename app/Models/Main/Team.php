<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Team extends Model
{
    use HasFactory;

    protected $table = 'main.team';

    protected $fillable = ['id', 'name', 'default_unit_id', 'company_id', 'quantity_collectors', 'contracting_company_id',
        'enabled', 'created_at', 'updated_at'];
}
